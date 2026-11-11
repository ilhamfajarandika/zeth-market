<x-layouts.app>
    @foreach ($categories as $category)
        <section class="mb-10">
            <h2 class="mb-4 text-xl font-semibold">{{ $category->name }}</h2>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($category->games as $game)
                    <a href="{{ route('game.show', $game) }}"
                       class="rounded-xl border border-white/10 bg-white/5 p-4 transition hover:border-violet-400">
                        <div class="mb-3 aspect-square rounded-lg bg-slate-800">
                            @if ($game->thumbnail)
                                <img src="{{ Storage::url($game->thumbnail) }}" alt="{{ $game->name }}" class="h-full w-full rounded-lg object-cover">
                            @endif
                        </div>
                        <p class="font-medium">{{ $game->name }}</p>
                        <p class="text-sm text-slate-400">{{ $game->publisher }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</x-layouts.app>
