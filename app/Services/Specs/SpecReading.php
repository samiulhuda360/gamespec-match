<?php

namespace App\Services\Specs;

/**
 * What was read from a description of a PC. Parts are catalogue rows (id, name, benchmark...)
 * so the form can select them; $how says where each one came from:
 * 'text' = a catalogue name found in the text, 'ai' = named by the model and confirmed in the
 * catalogue and in the text.
 */
final class SpecReading
{
    /** @var array{cpu?: string, gpu?: string, ram?: string} */
    public array $how = [];

    /** @var list<string> things the visitor should check by hand */
    public array $notes = [];

    public string $source = 'rules';

    /** The model was asked but the call failed, so only the rules answered. */
    public bool $modelFailed = false;

    public function __construct(
        public ?array $cpu = null,
        public ?array $gpu = null,
        public ?int $ramGb = null,
        public ?string $platform = null,
        public bool $laptop = false,
    ) {
    }

    /** @return list<string> */
    public function missing(): array
    {
        return array_keys(array_filter(['cpu' => $this->cpu === null, 'gpu' => $this->gpu === null, 'ram' => $this->ramGb === null]));
    }

    public function toArray(): array
    {
        $part = fn (?array $p, string $kind) => $p === null ? null : [
            'id' => $p['id'], 'name' => $p['name'], 'how' => $this->how[$kind] ?? 'text',
        ];

        return [
            'cpu' => $part($this->cpu, 'cpu'),
            'gpu' => $part($this->gpu, 'gpu'),
            'ram_gb' => $this->ramGb,
            'platform' => $this->platform,
            'laptop' => $this->laptop,
            'source' => $this->source,
            'notes' => $this->notes,
        ];
    }
}
