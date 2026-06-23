@php($url = $getRecord()->url)
<div class="rounded-lg overflow-hidden border border-gray-200 dark:border-white/10 bg-white">
    @if ($url)
        <div style="position:relative; width:100%; padding-top:72%; overflow:hidden;">
            <iframe
                src="{{ $url }}"
                loading="lazy"
                referrerpolicy="no-referrer"
                sandbox="allow-scripts allow-same-origin"
                style="position:absolute; top:0; left:0; width:200%; height:200%; transform:scale(0.5); transform-origin:top left; border:0;"
                title="Live-Vorschau"></iframe>
        </div>
        <div class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
            <span>Live-Vorschau (manche Seiten verbieten die Einbettung)</span>
            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline">öffnen ↗</a>
        </div>
    @else
        <div class="p-4 text-sm text-gray-500">Keine URL hinterlegt.</div>
    @endif
</div>
