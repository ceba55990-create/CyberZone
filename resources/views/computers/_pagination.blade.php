@if ($paginator->hasPages())
    <nav class="computer-pagination" aria-label="Навігація сторінками">
        <ul>
            <li>
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true">Назад</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Назад</a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span aria-disabled="true">{{ $element }}</span></li>
                @else
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" aria-label="Сторінка {{ $page }}">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Сторінка {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next">Далі</a>
                @else
                    <span aria-disabled="true">Далі</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
