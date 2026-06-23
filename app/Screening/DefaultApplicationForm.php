<?php

namespace App\Screening;

use App\Enums\FieldType;

class DefaultApplicationForm
{
    /**
     * The dwellow default application-form schema.
     *
     * Each unit is seeded with this ordered set of fields, which the landlord can
     * then customise. The defaults follow standard Canadian rental-screening norms:
     * applicants submit their own information and documents — dwellow never pulls
     * credit/background numbers (see `references.md` / ADR 0002).
     *
     * @return list<array{key: string, type: string, label: string, required: bool, help: ?string, options: ?array<int, string>}>
     */
    public static function fields(): array
    {
        return [
            // Identity & contact.
            self::field('first_name', FieldType::ShortText, 'First name', required: true),
            self::field('last_name', FieldType::ShortText, 'Last name', required: true),
            self::field('email', FieldType::ShortText, 'Email address', required: true),
            self::field('phone', FieldType::ShortText, 'Phone number', required: true),
            self::field('date_of_birth', FieldType::Date, 'Date of birth', required: true),

            // Current residence.
            self::field('current_address', FieldType::ShortText, 'Current address', required: true),
            self::field('current_move_in_date', FieldType::Date, 'Move-in date at current address'),
            self::field('current_monthly_rent', FieldType::Currency, 'Current monthly rent'),
            self::field('reason_for_leaving', FieldType::LongText, 'Reason for leaving'),

            // Employment & income.
            self::field('employer_name', FieldType::ShortText, 'Employer name', required: true),
            self::field('job_title', FieldType::ShortText, 'Job title'),
            self::field(
                'employment_type',
                FieldType::SingleChoice,
                'Employment type',
                options: ['Full-time', 'Part-time', 'Self-employed', 'Student', 'Unemployed'],
            ),
            self::field('gross_monthly_income', FieldType::Currency, 'Gross monthly income', required: true),
            self::field('employment_start_date', FieldType::Date, 'Employment start date'),

            // Occupancy.
            self::field('desired_move_in_date', FieldType::Date, 'Desired move-in date', required: true),
            self::field('number_of_occupants', FieldType::Number, 'Number of occupants', required: true),
            self::field('has_pets', FieldType::Boolean, 'Do you have any pets?'),
            self::field('pet_details', FieldType::LongText, 'Pet details', help: 'Type, breed, size, and number of pets.'),
            self::field('is_smoker', FieldType::Boolean, 'Do you smoke?'),

            // References.
            self::field(
                'previous_landlord',
                FieldType::Reference,
                'Previous or current landlord',
                help: 'Provide a landlord reference we can contact (name, email, phone, relationship).',
            ),

            // Documents.
            self::field('photo_id', FieldType::File, 'Government-issued photo ID', required: true),
            self::field('pay_stubs', FieldType::File, 'Recent pay stubs', required: true),
            self::field('proof_of_income', FieldType::File, 'Bank statement or additional proof of income'),

            // Consent.
            self::field(
                'screening_consent',
                FieldType::Consent,
                'Screening consent',
                required: true,
                help: 'I consent to the landlord using and verifying the information I have provided and '.
                    'contacting the references listed in this application. dwellow does not run credit or '.
                    'background checks — only the information and documents I submit are shared.',
            ),
        ];
    }

    /**
     * Build a single field shape with every key present.
     *
     * @param  ?array<int, string>  $options
     * @return array{key: string, type: string, label: string, required: bool, help: ?string, options: ?array<int, string>}
     */
    private static function field(
        string $key,
        FieldType $type,
        string $label,
        bool $required = false,
        ?string $help = null,
        ?array $options = null,
    ): array {
        return [
            'key' => $key,
            'type' => $type->value,
            'label' => $label,
            'required' => $required,
            'help' => $help,
            'options' => $options,
        ];
    }
}
