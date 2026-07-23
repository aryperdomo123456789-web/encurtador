@if(empty($items))
    <p class="empty">Sem dados ainda.</p>
@else
    <ul class="top-list">
        @foreach($items as $label => $count)
            <li>
                <span class="top-list-label">{{ $label ?: 'Direto' }}</span>
                <span class="top-list-count">{{ $count }}</span>
            </li>
        @endforeach
    </ul>
@endif
