<?php
// Lucide icons (ISC) inlined so no JS icon library is needed.
const ICONS = [
    "phone" => "<path d=\"M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384\" />",
    "menu" => "<path d=\"M4 5h16\" /> <path d=\"M4 12h16\" /> <path d=\"M4 19h16\" />",
    "x" => "<path d=\"M18 6 6 18\" /> <path d=\"m6 6 12 12\" />",
    "chevron-down" => "<path d=\"m6 9 6 6 6-6\" />",
    "chevron-right" => "<path d=\"m9 18 6-6-6-6\" />",
    "chevron-left" => "<path d=\"m15 18-6-6 6-6\" />",
    "arrow-right" => "<path d=\"M5 12h14\" /> <path d=\"m12 5 7 7-7 7\" />",
    "arrow-left" => "<path d=\"m12 19-7-7 7-7\" /> <path d=\"M19 12H5\" />",
    "check" => "<path d=\"M20 6 9 17l-5-5\" />",
    "circle-check" => "<circle cx=\"12\" cy=\"12\" r=\"10\" /> <path d=\"m16 9-5.5 5.5L8 12\" />",
    "mail" => "<path d=\"m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7\" /> <rect x=\"2\" y=\"4\" width=\"20\" height=\"16\" rx=\"2\" />",
    "map-pin" => "<path d=\"M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0\" /> <circle cx=\"12\" cy=\"10\" r=\"3\" />",
    "message-circle" => "<path d=\"M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092 10 10 0 1 0-4.777-4.719\" />",
    "package-check" => "<path d=\"M12 22V12\" /> <path d=\"m16 17 2 2 4-4\" /> <path d=\"M21 11.127V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.729l7 4a2 2 0 0 0 2 .001l1.32-.753\" /> <path d=\"M3.29 7 12 12l8.71-5\" /> <path d=\"m7.5 4.27 8.997 5.148\" />",
    "wrench" => "<path d=\"M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z\" />",
    "cog" => "<path d=\"M11 10.27 7 3.34\" /> <path d=\"m11 13.73-4 6.93\" /> <path d=\"M12 22v-2\" /> <path d=\"M12 2v2\" /> <path d=\"M14 12h8\" /> <path d=\"m17 20.66-1-1.73\" /> <path d=\"m17 3.34-1 1.73\" /> <path d=\"M2 12h2\" /> <path d=\"m20.66 17-1.73-1\" /> <path d=\"m20.66 7-1.73 1\" /> <path d=\"m3.34 17 1.73-1\" /> <path d=\"m3.34 7 1.73 1\" /> <circle cx=\"12\" cy=\"12\" r=\"2\" /> <circle cx=\"12\" cy=\"12\" r=\"8\" />",
    "gauge" => "<path d=\"m12 14 4-4\" /> <path d=\"M3.34 19a10 10 0 1 1 17.32 0\" />",
    "shield-check" => "<path d=\"M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z\" /> <path d=\"m9 12 2 2 4-4\" />",
    "calendar-days" => "<path d=\"M8 2v3\" /> <path d=\"M16 2v3\" /> <rect x=\"3\" y=\"3\" width=\"18\" height=\"18\" rx=\"2\" /> <path d=\"M3 9h18\" /> <path d=\"M8 13h.01\" /> <path d=\"M12 13h.01\" /> <path d=\"M16 13h.01\" /> <path d=\"M8 17h.01\" /> <path d=\"M12 17h.01\" /> <path d=\"M16 17h.01\" />",
];

function icon(string $name, string $class = "h-4 w-4"): string
{
    $body = ICONS[$name] ?? "";
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . htmlspecialchars($class, ENT_QUOTES) . '" aria-hidden="true">' . $body . '</svg>';
}
