<?php

namespace wulaphp\form;

interface IForm {
    public function excludeFields(string ...$fields);

    public function addField(string $field, $ann, $default = ''): ?string;

    public function alterFieldOptions(string $name, array &$options);

    public function createWidgets(): ?array;

    public function formData($excepts = '', $useDefault = false): array;

    public function inflate($excepts = '', $useDefault = false, $force = false): array;

    public function inflateByData(array $data): array;
}