@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="pagination-container">
        {{-- Information Text --}}
        <div class="pagination-info">
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong> &ndash; <strong>{{ $paginator->lastItem() }}</strong> dari <strong>{{ $paginator->total() }}</strong> data
        </div>

        {{-- Page Buttons --}}
        <ul class="pagination-nav">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="pagination-item">
                    <span class="pagination-btn pagination-prev is-disabled" aria-disabled="true">
                        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                        <span>Sebelumnya</span>
                    </span>
                </li>
            @else
                <li class="pagination-item">
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn pagination-prev" aria-label="Halaman Sebelumnya">
                        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                        <span>Sebelumnya</span>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- Three Dots Separator --}}
                @if (is_string($element))
                    <li class="pagination-item">
                        <span class="pagination-dots" aria-disabled="true">&hellip;</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="pagination-item">
                                <span class="pagination-link is-active" aria-current="page">{{ $page }}</span>
                            </li>
                        @else
                            <li class="pagination-item">
                                <a href="{{ $url }}" class="pagination-link" aria-label="Menuju halaman {{ $page }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination-item">
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn pagination-next" aria-label="Halaman Berikutnya">
                        <span>Berikutnya</span>
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                </li>
            @else
                <li class="pagination-item">
                    <span class="pagination-btn pagination-next is-disabled" aria-disabled="true">
                        <span>Berikutnya</span>
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@elseif($paginator->total() > 0)
    <div class="pagination-container" style="justify-content: flex-start;">
        <div class="pagination-info">
            Menampilkan seluruh <strong>{{ $paginator->total() }}</strong> data
        </div>
    </div>
@endif
