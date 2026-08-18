<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @php
        $url = $getState();
    @endphp

    @if (filled($url))
        <div class="space-y-2">
            <audio controls preload="metadata" class="w-full">
                <source src="{{ $url }}">
            </audio>
            <a
                href="{{ $url }}"
                target="_blank"
                rel="noopener"
                class="text-sm font-medium text-primary-600 hover:underline"
            >
                Aç / yüklə
            </a>
        </div>
    @else
        <span class="text-sm text-gray-500 dark:text-gray-400">Səs yazısı yoxdur</span>
    @endif
</x-dynamic-component>
