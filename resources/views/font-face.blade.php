@props(['name', 'weight', 'style', 'path'])

@font-face {
  font-display: swap;
  font-family: '{{ $name }}';
  font-style: {{ $style }};
  font-weight: {{ $weight }};
  src: url('{{ $path }}') format('woff2');
}
