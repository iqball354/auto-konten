<div fixed-plugin>
  <a fixed-plugin-button class="fixed px-4 py-2 text-xl bg-white shadow-lg cursor-pointer bottom-8 right-8 z-990 rounded-circle text-slate-700">
    <i class="py-2 pointer-events-none fa fa-cog"></i>
  </a>

  <div fixed-plugin-card class="z-sticky backdrop-blur-2xl backdrop-saturate-200 shadow-3xl w-90 ease -right-90 fixed top-0 left-auto flex h-full flex-col bg-white/80 px-2.5 duration-200">

    <div class="px-6 pt-4 pb-0">
      <div class="float-left">
        <h5 class="mt-4 mb-0">Argon Configurator</h5>
        <p>See dashboard options.</p>
      </div>

      <div class="float-right mt-6">
        <button fixed-plugin-close-button>
          <i class="fa fa-close"></i>
        </button>
      </div>
    </div>

    <hr class="my-2">

    <div class="flex-auto p-6 pt-0 overflow-auto">

      <h6 class="mb-0">Sidebar Colors</h6>

      <div class="my-2 text-left" sidenav-colors>
        <span class="h-6 w-6 inline-block bg-blue-500 rounded-full cursor-pointer"
          data-color="blue" onclick="sidebarColor(this)">
        </span>

        <span class="h-6 w-6 inline-block bg-gray-700 rounded-full cursor-pointer"
          data-color="gray" onclick="sidebarColor(this)">
        </span>

        <span class="h-6 w-6 inline-block bg-red-500 rounded-full cursor-pointer"
          data-color="red" onclick="sidebarColor(this)">
        </span>
      </div>

      <hr class="my-6">

      <div class="flex">
        <h6 class="mb-0">Navbar Fixed</h6>

        <div class="ml-auto">
          <input navbarFixed type="checkbox">
        </div>
      </div>

      <div class="flex mt-4">
        <h6 class="mb-0">Light / Dark</h6>

        <div class="ml-auto">
          <input dark-toggle type="checkbox">
        </div>
      </div>

    </div>

  </div>
</div>