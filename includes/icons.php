<?php
/**
 * 内联 SVG 图标库
 */

function icon(string $name, int $size = 20): string
{
    $paths = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'users' => '<path d="M16 20v-1.5c0-2.5-2-4.5-4.5-4.5h-3C6 14 4 16 4 18.5V20"/><circle cx="10" cy="8" r="3.5"/><path d="M17 14.2c1.8.6 3 2.2 3 4.3V20"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/>',
        'map-pin' => '<path d="M20 10.5c0 5.5-8 11-8 11S4 16 4 10.5a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10.5" r="2.8"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-3.9 3.6-7 8-7s8 3.1 8 7"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="m10 8-4 4 4 4M6 12h9"/>',
        'check' => '<path d="m4.5 12.5 5 5 10-11"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'arrow-right' => '<path d="M4 12h16M13 5l7 7-7 7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.5 2"/>',
        'trash' => '<path d="M4 7h16M9 7V4.5h6V7M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/>',
        'edit' => '<path d="M4 20h4L20 8l-4-4L4 16v4Z"/><path d="m14 6 4 4"/>',
        'upload' => '<path d="M12 16V4M7.5 8.5 12 4l4.5 4.5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
        'sparkles' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="m18.5 15.5.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1Z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'filter' => '<path d="M4 5h16l-6.5 8v6l-3 1.5V13L4 5Z"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/>',
        'lock' => '<rect x="4.5" y="10" width="15" height="10" rx="2.5"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/>',
        'shield' => '<path d="M12 3l7 3v5.5c0 4.4-3 8.2-7 9.5-4-1.3-7-5.1-7-9.5V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'trophy' => '<path d="M8 4h8v5a4 4 0 0 1-8 0V4Z"/><path d="M8 5.5H5V7a3 3 0 0 0 3 3"/><path d="M16 5.5h3V7a3 3 0 0 1-3 3"/><path d="M12 13v4"/><path d="M9 20h6"/><path d="M10 17h4v3h-4z"/>',
        'palette' => '<path d="M12 21a9 9 0 1 1 0-18c5 0 9 3.4 9 7.5 0 2.5-2 4.5-4.5 4.5H14a2 2 0 0 0-1.4 3.4A1.9 1.9 0 0 1 12 21Z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7.5" r="1"/><circle cx="15" cy="7.5" r="1"/>',
        'book' => '<path d="M4 5.5C4 4.7 4.7 4 5.5 4H11v16H5.5c-.8 0-1.5-.7-1.5-1.5v-13Z"/><path d="M20 5.5c0-.8-.7-1.5-1.5-1.5H13v16h5.5c.8 0 1.5-.7 1.5-1.5v-13Z"/>',
        'hand' => '<path d="M8 12V5.5a1.5 1.5 0 0 1 3 0V11M11 11V4.5a1.5 1.5 0 0 1 3 0V11M14 11V6a1.5 1.5 0 0 1 3 0v7"/><path d="M17 9.5a1.5 1.5 0 0 1 3 0V15c0 3.3-2.7 6-6 6h-1a6 6 0 0 1-4.8-2.4L5.2 15a1.6 1.6 0 0 1 2.4-2.1L9 14"/>',
        'leaf' => '<path d="M20 4C11 4 5 8.5 5 15a4 4 0 0 0 4 4c6.5 0 11-6 11-15Z"/><path d="M4 21c1.5-5 5-9 11-11"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="2"/><rect x="13" y="4" width="7" height="7" rx="2"/><rect x="4" y="13" width="7" height="7" rx="2"/><rect x="13" y="13" width="7" height="7" rx="2"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-9a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 4.6-7 9-7 9Z"/>',
        'external-link' => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>',
        'ticket' => '<path d="M4 8.5V7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1.5a3.5 3.5 0 0 0 0 7V17a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1.5a3.5 3.5 0 0 0 0-7Z"/><path d="M14 8v8"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'star' => '<path d="m12 3.5 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 10l6.1-.9L12 3.5Z"/>',
        'presentation' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M12 16v4M8.5 21h7"/>',
        'microphone' => '<rect x="9" y="3" width="6" height="10" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0"/><path d="M12 17.5V21"/>',
        'video' => '<rect x="3" y="6" width="12" height="12" rx="2.5"/><path d="m15 11 5.5-3.5v9L15 13"/>',
    ];

    $body = $paths[$name] ?? $paths['sparkles'];

    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true">' . $body . '</svg>';
}
