<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-muted']) }}>
    {{ $slot }}
</button>
