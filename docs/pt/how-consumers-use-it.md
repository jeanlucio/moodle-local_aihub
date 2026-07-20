# 🧩 Como os consumidores usam

Um plugin irmão consome o hub através de um guard em runtime e mantém o próprio fallback para `core_ai`:

```php
// Disponibilidade: chaves do hub primeiro, depois o fallback core_ai do consumidor.
public static function has_ai(): bool {
    if (class_exists(\local_aihub\ai::class) && \local_aihub\ai::is_available()) {
        return true;                       // BYOK via hub
    }
    return self::has_core_ai();            // funciona sem o hub
}

// Geração.
$result = \local_aihub\ai::generate_text(
    '',                    // prompt de sistema (opcional)
    $prompt,               // prompt do usuário
    false,                 // modo JSON
    'your_frankenstyle',   // 4º arg: componente chamador, para o log de uso
    'Rótulo curto'         // 5º arg: o que está sendo gerado, exibido no relatório de admin
);
if ($result['success']) {
    // $result['data'] é texto CRU e não-confiável — valide e passe por format_text().
}
```

O texto devolvido é **cru e não-confiável**: valide sua estrutura e passe por `format_text()` antes de exibir ou persistir.
