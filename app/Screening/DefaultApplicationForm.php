<?php

namespace App\Screening;

use App\Enums\FieldType;

class DefaultApplicationForm
{
    /**
     * The dwellow default application-form schema, grouped into sections.
     *
     * A landlord customises the form by choosing which *sections* to include —
     * not by editing individual fields. Each section bundles a coherent set of
     * fields (contact details, employment, etc.) so the builder stays simple and
     * the resulting application is consistent across landlords.
     *
     * The defaults follow standard Canadian rental-screening norms: applicants
     * submit their own information and documents — dwellow never pulls
     * credit/background numbers (see `references.md` / ADR 0002). The "Credit"
     * and "Background" sections therefore collect what the *applicant* discloses,
     * never a check dwellow runs.
     *
     * `locked` sections (Personal information, Consent) are always included and
     * cannot be switched off.
     *
     * @return list<array{key: string, label: string, description: string, locked: bool, enabled: bool, fields: list<array{key: string, type: string, label: string, required: bool, help: ?string, options: ?array<int, string>}>}>
     */
    public static function sections(): array
    {
        return [
            self::section(
                'personal_information',
                'Personal information',
                'Name and contact details we use to identify and reach the applicant.',
                locked: true,
                fields: [
                    self::field('first_name', FieldType::ShortText, 'First name', required: true),
                    self::field('last_name', FieldType::ShortText, 'Last name', required: true),
                    self::field('email', FieldType::ShortText, 'Email address', required: true),
                    self::field('phone', FieldType::ShortText, 'Phone number', required: true),
                    self::field('date_of_birth', FieldType::Date, 'Date of birth', required: true),
                ],
            ),

            self::section(
                'residence_history',
                'Residence history',
                'Where the applicant lives now and a landlord reference we can contact.',
                fields: [
                    self::field('current_address', FieldType::ShortText, 'Current address', required: true),
                    self::field('current_move_in_date', FieldType::Date, 'Move-in date at current address'),
                    self::field('current_monthly_rent', FieldType::Currency, 'Current monthly rent'),
                    self::field('reason_for_leaving', FieldType::LongText, 'Reason for leaving'),
                    self::field(
                        'previous_landlord',
                        FieldType::Reference,
                        'Previous or current landlord',
                        help: 'Provide a landlord reference we can contact (name, email, phone, relationship).',
                    ),
                ],
            ),

            self::section(
                'employment_income',
                'Employment & income',
                'Employment details, income, and supporting documents the applicant uploads.',
                fields: [
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
                    self::field('pay_stubs', FieldType::File, 'Recent pay stubs', required: true),
                    self::field('proof_of_income', FieldType::File, 'Bank statement or additional proof of income'),
                ],
            ),

            self::section(
                'household_occupancy',
                'Household & occupancy',
                'Who will live in the unit, move-in timing, pets, and smoking.',
                fields: [
                    self::field('desired_move_in_date', FieldType::Date, 'Desired move-in date', required: true),
                    self::field('number_of_occupants', FieldType::Number, 'Number of occupants', required: true),
                    self::field('has_pets', FieldType::Boolean, 'Do you have any pets?'),
                    self::field('pet_details', FieldType::LongText, 'Pet details', help: 'Type, breed, size, and number of pets.'),
                    self::field('is_smoker', FieldType::Boolean, 'Do you smoke?'),
                ],
            ),

            self::section(
                'identity_license',
                'Identity & license',
                'Government photo ID and, if relevant, a driver\'s licence number.',
                fields: [
                    self::field('photo_id', FieldType::File, 'Government-issued photo ID', required: true),
                    self::field('drivers_license_number', FieldType::ShortText, 'Driver\'s licence number'),
                ],
            ),

            self::section(
                'credit_information',
                'Credit information',
                'Credit details the applicant self-reports — dwellow does not pull credit reports.',
                fields: [
                    self::field(
                        'credit_score_range',
                        FieldType::SingleChoice,
                        'Estimated credit score',
                        options: ['Excellent (750+)', 'Good (700–749)', 'Fair (650–699)', 'Poor (below 650)', 'Not sure'],
                    ),
                    self::field(
                        'credit_report',
                        FieldType::File,
                        'Credit report',
                        help: 'Optionally upload a recent credit report you pulled yourself (e.g. Equifax or TransUnion).',
                    ),
                ],
            ),

            self::section(
                'background_check',
                'Background check',
                'Background details the applicant discloses — dwellow does not run background checks.',
                fields: [
                    self::field('ever_evicted', FieldType::Boolean, 'Have you ever been evicted?'),
                    self::field(
                        'eviction_details',
                        FieldType::LongText,
                        'Eviction details',
                        help: 'If you answered yes, briefly explain the circumstances.',
                    ),
                ],
            ),

            self::section(
                'consent',
                'Consent',
                'The applicant\'s consent to use and verify the information they provided.',
                locked: true,
                fields: [
                    self::field(
                        'screening_consent',
                        FieldType::Consent,
                        'Screening consent',
                        required: true,
                        help: 'I consent to the landlord using and verifying the information I have provided and '.
                            'contacting the references listed in this application. dwellow does not run credit or '.
                            'background checks — only the information and documents I submit are shared.',
                    ),
                ],
            ),
        ];
    }

    /**
     * The keys of every section in the catalog, in order.
     *
     * @return list<string>
     */
    public static function sectionKeys(): array
    {
        return array_map(fn (array $section): string => $section['key'], self::sections());
    }

    /**
     * Rebuild the section catalog with only the given section keys enabled.
     *
     * The catalog is the single source of truth: a landlord's saved form is the
     * canonical sections with their `enabled` flags set from the selection.
     * Locked sections are always enabled regardless of the selection.
     *
     * @param  array<int, string>  $enabledKeys
     * @return list<array<string, mixed>>
     */
    public static function withEnabledSections(array $enabledKeys): array
    {
        $selected = array_flip($enabledKeys);

        return array_map(function (array $section) use ($selected): array {
            $section['enabled'] = ($section['locked'] ?? false) === true || isset($selected[$section['key']]);

            return $section;
        }, self::sections());
    }

    /**
     * Build a single section shape.
     *
     * @param  list<array{key: string, type: string, label: string, required: bool, help: ?string, options: ?array<int, string>}>  $fields
     * @return array{key: string, label: string, description: string, locked: bool, enabled: bool, fields: list<array<string, mixed>>}
     */
    private static function section(
        string $key,
        string $label,
        string $description,
        array $fields,
        bool $locked = false,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'locked' => $locked,
            'enabled' => true,
            'fields' => $fields,
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
