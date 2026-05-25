<?php

namespace App\Services;

use App\Models\PaymentSetting;
use App\Models\SosialAccount;
use App\Models\SosialPost;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfileService
{
	/**
	 * @return array{user: User, sosial_accounts: \Illuminate\Database\Eloquent\Collection<int, SosialAccount>, subscription: ?Subscription, statPost: array{total:int, berhasil:int, gagal:int}, qrisCode:?string, qrisPreview:?string}
	 */
	public function getProfilePageData(int $userId): array
	{
		$user = User::query()->findOrFail($userId);

		$sosialAccounts = SosialAccount::query()
			->where('user_id', $userId)
			->whereNull('deleted_at')
			->where('is_active', 1)
			->orderBy('platform')
			->get();

		$subscription = Subscription::query()
			->where('user_id', $userId)
			->active()
			->latest()
			->first();

		$postLogStats = DB::table('post_logs')
			->join('sosial_post', 'post_logs.post_id', '=', 'sosial_post.id')
			->where('sosial_post.user_id', $userId)
			->selectRaw('
				COUNT(CASE WHEN post_logs.status = "success" THEN 1 END) as berhasil,
				COUNT(CASE WHEN post_logs.status = "failed" THEN 1 END) as gagal
			')
			->first();

		$statPost = [
			'total' => SosialPost::query()
				->where('user_id', $userId)
				->whereNull('deleted_at')
				->count(),
			'berhasil' => (int) ($postLogStats->berhasil ?? 0),
			'gagal' => (int) ($postLogStats->gagal ?? 0),
		];

		$qrisCode = null;
		$qrisPreview = null;

		if (($user->role ?? null) === 'admin') {
			$qrisCode = (string) PaymentSetting::get('qris_code', '');
			$qrisPreview = $this->buildQrisPreview($qrisCode);
		}

		return [
			'user' => $user,
			'sosial_accounts' => $sosialAccounts,
			'subscription' => $subscription,
			'statPost' => $statPost,
			'qrisCode' => $qrisCode,
			'qrisPreview' => $qrisPreview,
		];
	}

	public function updateProfile(Request $request, int $userId): void
	{
		DB::transaction(function () use ($request, $userId): void {
			$user = User::query()->findOrFail($userId);

			$avatarPath = null;

			if ($request->hasFile('avatar')) {
				if (!empty($user->avatar) && Storage::disk('public')->exists($user->avatar)) {
					Storage::disk('public')->delete($user->avatar);
				}

				$avatarPath = $request->file('avatar')->store('avatars', 'public');
			}

			$user->name = $request->name;
			$user->email = $request->email;

			if ($avatarPath !== null) {
				$user->avatar = $avatarPath;
			}

			$user->save();

			if (($user->role ?? null) === 'admin' && $request->hasFile('qris_image')) {
				$existingQris = (string) PaymentSetting::get('qris_code', '');

				if ($this->shouldDeleteStoredQris($existingQris)) {
					Storage::disk('public')->delete($existingQris);
				}

				$qrisPath = $request->file('qris_image')->store('qris', 'public');
				PaymentSetting::set('qris_code', $qrisPath, 'Gambar QRIS');
			}
		});
	}

	public function updatePassword(Request $request, int $userId): void
	{
		DB::transaction(function () use ($request, $userId): void {
			$user = User::query()->findOrFail($userId);

			if (!Hash::check($request->password_lama, $user->password)) {
				throw new RuntimeException('Password lama tidak sesuai.');
			}

			if (Hash::check($request->password_baru, $user->password)) {
				throw new RuntimeException('Password baru tidak boleh sama dengan password lama.');
			}

			$user->update([
				'password' => Hash::make($request->password_baru),
			]);
		});
	}

	private function buildQrisPreview(?string $qrisCode): ?string
	{
		if (empty($qrisCode)) {
			return null;
		}

		if (
			str_starts_with($qrisCode, 'http://')
			|| str_starts_with($qrisCode, 'https://')
			|| str_starts_with($qrisCode, 'data:image/')
		) {
			return $qrisCode;
		}

		return asset('storage/' . ltrim($qrisCode, '/'));
	}

	private function shouldDeleteStoredQris(string $qrisPath): bool
	{
		if (empty($qrisPath)) {
			return false;
		}

		if (
			str_starts_with($qrisPath, 'http://')
			|| str_starts_with($qrisPath, 'https://')
			|| str_starts_with($qrisPath, 'data:image/')
		) {
			return false;
		}

		return Storage::disk('public')->exists($qrisPath);
	}
}
