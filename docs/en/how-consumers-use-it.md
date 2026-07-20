# 🧩 How consumers use it

A sibling plugin consumes the hub through a runtime guard and keeps its own `core_ai` fallback:

```php
// Availability: hub keys first, then the consumer's own core_ai fallback.
public static function has_ai(): bool {
    if (class_exists(\local_aihub\ai::class) && \local_aihub\ai::is_available()) {
        return true;                       // BYOK via the hub
    }
    return self::has_core_ai();            // works without the hub
}

// Generation.
$result = \local_aihub\ai::generate_text(
    '',                    // system prompt (optional)
    $prompt,               // user prompt
    false,                 // JSON mode
    'your_frankenstyle',   // 4th arg: caller component, for the usage log
    'Short label'          // 5th arg: what is being generated, shown in the admin report
);
if ($result['success']) {
    // $result['data'] is RAW, untrusted text — validate and format_text() it.
}
```

The returned text is **raw and untrusted**: validate its structure and pass it through `format_text()` before displaying or persisting it.
