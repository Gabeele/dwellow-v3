<?php

namespace App\Enums;

enum FieldType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Number = 'number';
    case Currency = 'currency';
    case Date = 'date';
    case SingleChoice = 'single_choice';
    case MultiChoice = 'multi_choice';
    case Boolean = 'boolean';
    case File = 'file';
    case Reference = 'reference';
    case Consent = 'consent';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::ShortText => 'Short text',
            self::LongText => 'Long text',
            self::Number => 'Number',
            self::Currency => 'Currency',
            self::Date => 'Date',
            self::SingleChoice => 'Single choice',
            self::MultiChoice => 'Multiple choice',
            self::Boolean => 'Yes / no',
            self::File => 'File upload',
            self::Reference => 'Reference',
            self::Consent => 'Consent',
        };
    }

    /**
     * Whether this field type requires a predefined list of options.
     */
    public function expectsOptions(): bool
    {
        return match ($this) {
            self::SingleChoice, self::MultiChoice => true,
            default => false,
        };
    }

    /**
     * Whether this field type captures an uploaded file.
     */
    public function isFileUpload(): bool
    {
        return $this === self::File;
    }
}
