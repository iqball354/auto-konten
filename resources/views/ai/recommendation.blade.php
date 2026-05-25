@extends('layouts.app')

@section('content')
<div class="min-h-screen" style="background: rgba(16, 25, 42, 0.95);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" 
                     style="background: linear-gradient(135deg, #a2b7ff 0%, #7b9aff 100%);">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold" style="color: #e7efff;">AI Recommendation</h1>
            </div>
            <p style="color: #a2b7ff;">Analisis data post Anda dan dapatkan rekomendasi waktu posting terbaik</p>
        </div>

        <!-- Account Selector -->
        @if($accounts->count() > 0)
        <div class="mb-6">
            <label class="block text-sm font-medium mb-2" style="color: #a2b7ff;">Pilih Akun:</label>
            <select id="accountSelect" class="w-full md:w-64 px-4 py-2 rounded-lg border" 
                    style="background: rgba(26, 37, 58, 0.8); border-color: rgba(162, 183, 255, 0.12); color: #e7efff;">
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" 
                            @if($account->id === ($selectedAccount->id ?? null)) selected @endif>
                        {{ $account->username }} ({{ ucfirst($account->platform) }})
                    </option>
                @endforeach
            </select>
        </div>
        @else
        <div class="bg-yellow-500 bg-opacity-20 border border-yellow-500 border-opacity-30 rounded-lg p-4 mb-6">
            <p style="color: #fbbf24;">Anda belum memiliki akun yang terhubung. 
                <a href="{{ route('akun_terhubung') }}" class="underline hover:text-yellow-300">Hubungkan akun Anda</a>
            </p>
        </div>
        @endif

        <!-- Loading State -->
        <div id="loadingState" class="hidden">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin mr-3">
                    <svg class="w-8 h-8" style="color: #a2b7ff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <p style="color: #a2b7ff;">Memuat rekomendasi...</p>
            </div>
        </div>

        <!-- Content -->
        <div id="contentState" class="hidden">
            
            <!-- Default Data Alert -->
            <div id="defaultAlert" class="bg-blue-500 bg-opacity-20 border border-blue-500 border-opacity-30 rounded-lg p-4 mb-6 hidden">
                <div class="flex gap-3">
                    <svg class="w-6 h-6 flex-shrink-0" style="color: #7b9aff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold" style="color: #7b9aff;">Data masih belum cukup</p>
                        <p style="color: #a2b7ff;">Rekomendasi berbasis data standar industri. Terus posting untuk hasil yang lebih akurat!</p>
                    </div>
                </div>
            </div>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

                <!-- Best Hours Card -->
                <div class="lg:col-span-2" style="background: rgba(26, 37, 58, 0.8); border: 1px solid rgba(162, 183, 255, 0.12);" 
                     class="rounded-xl p-6">
                    <h2 class="text-xl font-semibold mb-4" style="color: #e7efff;">
                        <span class="inline-flex items-center gap-2">
                            <svg class="w-5 h-5" style="color: #a2b7ff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Jam Terbaik Posting
                        </span>
                    </h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4" id="bestHoursContainer">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

                <!-- Best Days Card -->
                <div style="background: rgba(26, 37, 58, 0.8); border: 1px solid rgba(162, 183, 255, 0.12);" 
                     class="rounded-xl p-6">
                    <h2 class="text-xl font-semibold mb-4" style="color: #e7efff;">
                        <span class="inline-flex items-center gap-2">
                            <svg class="w-5 h-5" style="color: #a2b7ff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Hari Terbaik
                        </span>
                    </h2>
                    
                    <div class="space-y-3" id="bestDaysContainer">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

            </div>

            <!-- Chart Section -->
            <div style="background: rgba(26, 37, 58, 0.8); border: 1px solid rgba(162, 183, 255, 0.12);" 
                 class="rounded-xl p-6">
                <h2 class="text-xl font-semibold mb-4" style="color: #e7efff;">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-5 h-5" style="color: #a2b7ff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Analisis 24 Jam
                    </span>
                </h2>
                
                <div class="relative h-80">
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-6 mt-6 justify-center md:justify-start">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-1 rounded" style="background: #7b9aff;"></div>
                    <span style="color: #a2b7ff;">Data Mentah</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-1 rounded" style="background: #a2b7ff;"></div>
                    <span style="color: #a2b7ff;">SMA (Simple Moving Avg)</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-1 rounded" style="background: #fbbf24;"></div>
                    <span style="color: #a2b7ff;">WMA (Weighted Moving Avg)</span>
                </div>
            </div>

        </div>

        <!-- Error State -->
        <div id="errorState" class="hidden">
            <div class="bg-red-500 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-6">
                <div class="flex gap-3">
                    <svg class="w-6 h-6 flex-shrink-0" style="color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold" style="color: #fca5a5;">Terjadi kesalahan</p>
                        <p id="errorMessage" style="color: #a2b7ff;">Gagal memuat data rekomendasi</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
    let chart = null;
    const accountSelect = document.getElementById('accountSelect');

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (accountSelect) {
            loadRecommendation();
            accountSelect.addEventListener('change', loadRecommendation);
        } else {
            document.getElementById('contentState').classList.add('hidden');
            document.getElementById('loadingState').classList.add('hidden');
        }
    });

    function loadRecommendation() {
        const accountId = accountSelect.value;
        
        if (!accountId) {
            showError('Pilih akun terlebih dahulu');
            return;
        }

        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('contentState').classList.add('hidden');
        document.getElementById('errorState').classList.add('hidden');

        Promise.all([
            fetch(`/ai/recommendation/data?account_id=${accountId}`).then(r => r.json()),
            fetch(`/ai/recommendation/chart?account_id=${accountId}`).then(r => r.json())
        ])
        .then(([recData, chartData]) => {
            if (!recData.success) {
                showError(recData.message);
                return;
            }

            // Show default alert if needed
            if (recData.is_default) {
                document.getElementById('defaultAlert').classList.remove('hidden');
            } else {
                document.getElementById('defaultAlert').classList.add('hidden');
            }

            // Render best hours
            renderBestHours(recData.hours || []);

            // Render best days
            renderBestDays(recData.days || []);

            // Render chart
            if (chartData.success) {
                renderChart(chartData);
            }

            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('contentState').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Gagal memuat data rekomendasi');
        });
    }

    function renderBestHours(hours) {
        const container = document.getElementById('bestHoursContainer');
        container.innerHTML = '';

        if (hours.length === 0) {
            container.innerHTML = '<p style="color: #a2b7ff;">Data tidak tersedia</p>';
            return;
        }

        hours.forEach(hour => {
            const percentage = Math.min(100, (hour.score / 100) * 100);
            const card = document.createElement('div');
            card.style.cssText = 'background: rgba(123, 154, 255, 0.1); border: 1px solid rgba(123, 154, 255, 0.3);';
            card.className = 'rounded-lg p-4';
            card.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <div class="text-2xl font-bold" style="color: #e7efff;">${String(hour.hour).padStart(2, '0')}:00</div>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-sm font-semibold" 
                          style="background: linear-gradient(135deg, #a2b7ff 0%, #7b9aff 100%); color: white;">
                        #${hour.rank}
                    </span>
                </div>
                <div class="w-full h-2 rounded-full" style="background: rgba(162, 183, 255, 0.2);">
                    <div class="h-full rounded-full" 
                         style="width: ${percentage}%; background: linear-gradient(90deg, #7b9aff, #a2b7ff);"></div>
                </div>
                <p class="text-xs mt-2" style="color: #a2b7ff;">Score: ${hour.score}</p>
            `;
            container.appendChild(card);
        });
    }

    function renderBestDays(days) {
        const container = document.getElementById('bestDaysContainer');
        container.innerHTML = '';

        if (days.length === 0) {
            container.innerHTML = '<p style="color: #a2b7ff;">Data tidak tersedia</p>';
            return;
        }

        days.forEach(day => {
            const percentage = Math.min(100, (day.score / 100) * 100);
            const bar = document.createElement('div');
            bar.innerHTML = `
                <div class="flex items-center justify-between mb-1">
                    <span style="color: #e7efff;" class="font-medium">${day.name}</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold" 
                          style="background: rgba(251, 191, 36, 0.2); color: #fbbf24;">
                        #${day.rank}
                    </span>
                </div>
                <div class="w-full h-2 rounded-full" style="background: rgba(162, 183, 255, 0.2);">
                    <div class="h-2 rounded-full" 
                         style="width: ${percentage}%; background: linear-gradient(90deg, #fbbf24, #f59e0b);"></div>
                </div>
            `;
            container.appendChild(bar);
        });
    }

    function renderChart(chartData) {
        const ctx = document.getElementById('engagementChart').getContext('2d');
        
        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Data Mentah',
                        data: chartData.rawData,
                        borderColor: '#7b9aff',
                        backgroundColor: 'rgba(123, 154, 255, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#7b9aff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1,
                    },
                    {
                        label: 'SMA (7)',
                        data: chartData.smaData,
                        borderColor: '#a2b7ff',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0,
                        tension: 0.4,
                    },
                    {
                        label: 'WMA (7)',
                        data: chartData.wmaData,
                        borderColor: '#fbbf24',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0,
                        tension: 0.4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: '#a2b7ff',
                            font: {
                                size: 12,
                            },
                            usePointStyle: true,
                            padding: 15,
                        }
                    },
                    filler: {
                        propagate: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(162, 183, 255, 0.1)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#a2b7ff',
                            font: {
                                size: 11,
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(162, 183, 255, 0.1)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#a2b7ff',
                            font: {
                                size: 11,
                            }
                        }
                    }
                }
            }
        });
    }

    function showError(message) {
        document.getElementById('errorMessage').textContent = message;
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('contentState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
    }
</script>
@endsection
