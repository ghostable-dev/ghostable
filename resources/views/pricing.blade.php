<x-layouts.guest title="Ghostable - Pricing">
    
    @include('partials.site-header')
    
    <div class="bg-white py-24 sm:py-32">
  <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
    <h1 class="text-5xl font-semibold tracking-tight text-gray-900 sm:text-6xl">Pricing that Grows with Your Organization</h1>
    <p class="mt-6 max-w-2xl mx-auto text-lg text-gray-600 sm:text-xl">Start free for personal use, upgrade as you grow—only pay for the seats and features you need.</p>
  </div>

  <div class="mt-16 relative mx-auto max-w-7xl px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
      
      <!-- Free Personal Plan -->
      <div class="-m-2 grid grid-cols-1 rounded-4xl shadow-lg ring-1 ring-black/5">
        <div class="grid grid-cols-1 rounded-4xl p-2 bg-white shadow-md">
          <div class="rounded-3xl p-10 pb-9 bg-white shadow-xl ring-1 ring-black/5">
            <h2 class="text-sm font-semibold text-brand">Free Personal <span class="sr-only">plan</span></h2>
            <p class="mt-2 text-sm text-gray-600">Perfect for one project or hobby apps.</p>

            <div class="mt-8 flex items-center gap-4">
              <div class="text-5xl font-semibold text-gray-900">Free</div>
              <div class="text-sm text-gray-600">
                <p>Personal use</p>
              </div>
            </div>

            <div class="mt-8">
                <flux:button href="#" variant="primary">Get started</flux:button>
            </div>

            <ul class="mt-8 space-y-3 text-sm text-gray-600">
              <li>✓ 1 user (no invites)</li>
              <li>✓ Up to 1 project, 2 environments</li>
              <li>✓ Basic validation</li>
              <li>✓ 1 read-only API token</li>
              <li>✓ Community support</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Solo Pro Plan -->
      <div class="-m-2 grid grid-cols-1 rounded-4xl shadow-lg ring-1 ring-black/5">
        <div class="grid grid-cols-1 rounded-4xl p-2 bg-white shadow-md">
          <div class="rounded-3xl p-10 pb-9 bg-white shadow-xl ring-1 ring-black/5">
            <h2 class="text-sm font-semibold text-brand">Solo Pro <span class="sr-only">plan</span></h2>
            <p class="mt-2 text-sm text-gray-600">For power users with multiple personal projects.</p>

            <div class="mt-8 flex items-center gap-4">
              <div class="text-5xl font-semibold text-gray-900">$5</div>
              <div class="text-sm text-gray-600">
                <p>per month</p>
                <p>1 user</p>
              </div>
            </div>

            <div class="mt-8">
              <flux:button href="#" variant="primary">Get started</flux:button>
            </div>

            <ul class="mt-8 space-y-3 text-sm text-gray-600">
              <li>✓ Unlimited projects & environments</li>
              <li>✓ Advanced validation rules</li>
              <li>✓ Read & write API tokens</li>
              <li>✓ Priority email support</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Organization Plan -->
      <div class="-m-2 grid grid-cols-1 rounded-4xl shadow-lg ring-1 ring-black/5">
        <div class="grid grid-cols-1 rounded-4xl p-2 bg-white shadow-md">
          <div class="rounded-3xl p-10 pb-9 bg-white shadow-xl ring-1 ring-black/5">
            <h2 class="text-sm font-semibold text-brand">Organization <span class="sr-only">plan</span></h2>
            <p class="mt-2 text-sm text-gray-600">Collaborate securely—scaled for small organizations.</p>

            <div class="mt-8 flex items-center gap-4">
              <div class="text-5xl font-semibold text-gray-900">$5<span class="text-base">/user</span></div>
              <div class="text-sm text-gray-600">
                <p>per month</p>
              </div>
            </div>

            <div class="mt-8">
                <flux:button href="#" variant="primary">Get started</flux:button>
            </div>

            <ul class="mt-8 space-y-3 text-sm text-gray-600">
              <li>✓ Multi-user invites & RBAC</li>
              <li>✓ Up to 5 projects (unlimited envs)</li>
              <li>✓ Full validation & audit logs</li>
              <li>✓ Multiple API tokens</li>
              <li>✓ Email & chat support</li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
    
</x-layouts.guest>