<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationLink;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seed a handful of realistic applicants (with uploaded documents) against the
 * test landlord's units, so the applicants list and the review page have
 * something to show out of the box.
 *
 * Documents are written as real (minimal but valid) PDFs onto the private disk
 * so the download links work end to end.
 */
class ApplicantSeeder extends Seeder
{
    /**
     * The applicant profiles to seed. `ratio` drives the income-to-rent figure
     * shown on the review page (income is derived from the unit's rent), and
     * `docs` lists the file fields to attach as fake PDFs.
     *
     * @var list<array<string, mixed>>
     */
    private const PROFILES = [
        [
            'first' => 'Maya', 'last' => 'Okafor',
            'ratio' => 3.4, 'credit' => 'Excellent (750+)',
            'employment' => 'Full-time', 'employer' => 'Northwind Logistics',
            'job' => 'Operations Manager', 'occupants' => 2,
            'status' => 'reviewing', 'days_ago' => 3,
            'source' => 'Facebook Marketplace',
            'notes' => 'Strong income and clean references — front-runner for this unit.',
            'docs' => ['photo_id', 'pay_stubs', 'proof_of_income'],
        ],
        [
            'first' => 'Daniel', 'last' => 'Reyes',
            'ratio' => 2.6, 'credit' => 'Good (700–749)',
            'employment' => 'Full-time', 'employer' => 'Cedar Health',
            'job' => 'Registered Nurse', 'occupants' => 1,
            'status' => 'new', 'days_ago' => 2,
            'source' => 'Kijiji',
            'notes' => null,
            'docs' => ['photo_id', 'pay_stubs'],
        ],
        [
            'first' => 'Priya', 'last' => 'Sharma',
            'ratio' => 2.9, 'credit' => 'Good (700–749)',
            'employment' => 'Self-employed', 'employer' => 'Sharma Design Studio',
            'job' => 'Freelance Designer', 'occupants' => 2,
            'status' => 'approved', 'days_ago' => 6,
            'source' => 'Referral',
            'notes' => 'Approved — lease sent.',
            'docs' => ['photo_id', 'pay_stubs', 'proof_of_income'],
        ],
        [
            'first' => 'Marcus', 'last' => 'Bell',
            'ratio' => 1.9, 'credit' => 'Fair (650–699)',
            'employment' => 'Part-time', 'employer' => 'Riverside Cafe',
            'job' => 'Barista', 'occupants' => 1,
            'status' => 'new', 'days_ago' => 1,
            'source' => 'Shared link',
            'notes' => null,
            'docs' => ['pay_stubs'],
        ],
        [
            'first' => 'Ava', 'last' => 'Thompson',
            'ratio' => 1.4, 'credit' => 'Poor (below 650)',
            'employment' => 'Student', 'employer' => 'University of British Columbia',
            'job' => 'Graduate Student', 'occupants' => 1,
            'status' => 'rejected', 'days_ago' => 5,
            'source' => 'Shared link',
            'notes' => 'Income below the 2× threshold for this unit.',
            'docs' => ['photo_id'],
        ],
    ];

    /**
     * Seed the applicants.
     */
    public function run(): void
    {
        $landlord = User::query()->where('email', 'landlord@example.com')->first();

        if (! $landlord) {
            return;
        }

        $units = Unit::query()
            ->whereHas('property', fn ($query) => $query->where('landlord_id', $landlord->id))
            ->orderBy('id')
            ->get();

        if ($units->isEmpty()) {
            return;
        }

        foreach (self::PROFILES as $index => $profile) {
            $unit = $units[$index % $units->count()];

            // The public flow now resolves-or-defaults, but provision the form
            // up front so the seeded snapshot matches what an applicant saw.
            $unit->applicationFormOrDefault();

            // The token is normally set by a creating-hook that model events
            // suppress during seeding, so assign it explicitly.
            $link = ApplicationLink::factory()->for($unit)->make([
                'label' => $profile['source'],
            ]);
            $link->forceFill(['token' => Str::random(40)])->save();

            $this->seedApplication($unit, $link, $profile);
        }
    }

    /**
     * Create one application (with documents) for a unit from a profile.
     *
     * @param  array<string, mixed>  $profile
     */
    private function seedApplication(Unit $unit, ApplicationLink $link, array $profile): void
    {
        $rent = (float) ($unit->rent_amount ?? 2000);
        $income = (int) round(($rent * (float) $profile['ratio']) / 50) * 50;
        $status = ApplicationStatus::from($profile['status']);
        $submittedAt = Carbon::now()->subDays((int) $profile['days_ago']);

        $application = Application::factory()->for($link)->make([
            'applicant_first_name' => $profile['first'],
            'applicant_last_name' => $profile['last'],
            'applicant_email' => Str::lower($profile['first'].'.'.$profile['last']).'@example.com',
            'applicant_phone' => fake()->numerify('### ###-####'),
            'answers' => $this->answersFor($profile, $income, $rent),
            'status' => $status,
            'landlord_notes' => $profile['notes'],
            'submitted_at' => $submittedAt,
        ]);

        // public_id, unit_id and status_changed_at are guarded (and the public_id
        // creating-hook is suppressed during seeding), so set them directly.
        $application->forceFill([
            'public_id' => (string) Str::ulid(),
            'unit_id' => $unit->id,
            'status_changed_at' => $status === ApplicationStatus::New
                ? null
                : $submittedAt->copy()->addDay(),
        ])->save();

        $this->attachDocuments($application, $profile['first'], $profile['docs']);
    }

    /**
     * Build a full set of answers for the default application form.
     *
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function answersFor(array $profile, int $income, float $rent): array
    {
        $hasPets = fake()->boolean(30);
        $evicted = $profile['status'] === 'rejected';

        return [
            // Personal information
            'first_name' => $profile['first'],
            'last_name' => $profile['last'],
            'email' => Str::lower($profile['first'].'.'.$profile['last']).'@example.com',
            'phone' => fake()->numerify('### ###-####'),
            'date_of_birth' => fake()->dateTimeBetween('-45 years', '-21 years')->format('Y-m-d'),

            // Residence history
            'current_address' => fake()->buildingNumber().' '.fake()->streetName().', '.fake()->city().', BC',
            'current_move_in_date' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'current_monthly_rent' => (int) round($rent * 0.9),
            'reason_for_leaving' => fake()->randomElement([
                'Looking for a larger place closer to work.',
                'Current lease is ending and the unit is being sold.',
                'Relocating to be nearer to family.',
            ]),
            'previous_landlord' => [
                'name' => fake()->name(),
                'relationship' => 'Current landlord',
                'email' => fake()->safeEmail(),
                'phone' => fake()->numerify('### ###-####'),
            ],

            // Employment & income
            'employer_name' => $profile['employer'],
            'job_title' => $profile['job'],
            'employment_type' => $profile['employment'],
            'gross_monthly_income' => $income,
            'employment_start_date' => fake()->dateTimeBetween('-8 years', '-6 months')->format('Y-m-d'),
            'pay_stubs' => in_array('pay_stubs', $profile['docs'], true) ? 'pay-stubs.pdf' : null,
            'proof_of_income' => in_array('proof_of_income', $profile['docs'], true) ? 'bank-statement.pdf' : null,

            // Household & occupancy
            'desired_move_in_date' => Carbon::now()->addWeeks(fake()->numberBetween(2, 8))->format('Y-m-d'),
            'number_of_occupants' => $profile['occupants'],
            'has_pets' => $hasPets,
            'pet_details' => $hasPets ? 'One small, spayed indoor cat.' : null,
            'is_smoker' => false,

            // Identity & license
            'photo_id' => in_array('photo_id', $profile['docs'], true) ? 'government-id.pdf' : null,
            'drivers_license_number' => fake()->bothify('?######'),

            // Credit information
            'credit_score_range' => $profile['credit'],
            'credit_report' => null,

            // Background check
            'ever_evicted' => $evicted,
            'eviction_details' => $evicted
                ? 'A lease was terminated early in 2021 following a dispute that has since been resolved.'
                : null,

            // Consent
            'screening_consent' => true,
        ];
    }

    /**
     * Write a fake PDF for each requested file field and record it as a Document.
     *
     * @param  list<string>  $docs
     */
    private function attachDocuments(Application $application, string $firstName, array $docs): void
    {
        $names = [
            'photo_id' => ['government-id.pdf', 'Government ID'],
            'pay_stubs' => ['pay-stubs.pdf', 'Pay stubs (2 months)'],
            'proof_of_income' => ['bank-statement.pdf', 'Bank statement'],
        ];

        foreach ($docs as $fieldKey) {
            [$filename, $label] = $names[$fieldKey];
            $path = "applications/{$application->id}/{$filename}";
            $contents = $this->fakePdf("{$label} — {$firstName}");

            Storage::disk('local')->put($path, $contents);

            $application->documents()->create([
                'field_key' => $fieldKey,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $filename,
                'mime_type' => 'application/pdf',
                'size' => strlen($contents),
            ]);
        }
    }

    /**
     * Build a minimal, valid single-page PDF whose body shows the given title.
     * Byte offsets are computed so the xref table is correct and the file opens.
     */
    private function fakePdf(string $title): string
    {
        $stream = 'BT /F1 20 Tf 48 144 Td ('.addcslashes($title, '()\\').') Tj ET';

        $objects = [
            1 => '<</Type /Catalog /Pages 2 0 R>>',
            2 => '<</Type /Pages /Kids [3 0 R] /Count 1>>',
            3 => '<</Type /Page /Parent 2 0 R /MediaBox [0 0 612 216] '.
                '/Contents 4 0 R /Resources <</Font <</F1 5 0 R>>>>>>',
            4 => '<</Length '.strlen($stream).">>\nstream\n".$stream."\nendstream",
            5 => '<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $size = count($objects) + 1;

        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";

        foreach ($objects as $number => $body) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        $pdf .= "trailer\n<</Size {$size} /Root 1 0 R>>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
