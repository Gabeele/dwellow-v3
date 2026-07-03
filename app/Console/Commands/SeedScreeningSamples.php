<?php

namespace App\Console\Commands;

use App\Jobs\ScoreApplication;
use App\Models\ApplicationLink;
use App\Models\Unit;
use App\Models\User;
use App\Screening\ApplicationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

#[Signature('screening:seed-samples
    {--count=3 : How many applications to create (cycles through the sample profiles)}
    {--sync : Score each application immediately instead of queueing the job}
    {--landlord=landlord@example.com : Email of the landlord whose units receive the applications}')]
#[Description('Create sample screening applications from the fixture documents and fire the scoring service.')]
class SeedScreeningSamples extends Command
{
    /**
     * Where the rendered sample documents live (one folder per profile, each with
     * the four PDFs generated from the markdown sources).
     */
    private const FIXTURES = 'tests/Fixtures/screening-samples';

    /**
     * Build sample applications through the real submission workflow and fire the
     * scoring service for each — the same path a live submission takes.
     *
     * Each application reuses one profile's document set (the rendered markdowns)
     * and a matching set of answers, spread across the landlord's units. Scoring
     * is queued by default ({@see ApplicationService::requestScore()}); `--sync`
     * runs it inline so the Scores are ready the moment the command returns.
     */
    public function handle(ApplicationService $service): int
    {
        $dir = base_path(self::FIXTURES);

        if (! is_dir($dir)) {
            $this->error('Sample documents not found at '.self::FIXTURES.'. Generate them first.');

            return self::FAILURE;
        }

        $landlord = User::where('email', $this->option('landlord'))->first();
        $units = $landlord
            ? Unit::whereHas('property', fn ($query) => $query->where('landlord_id', $landlord->id))->get()
            : Unit::all();

        if ($units->isEmpty()) {
            $this->error('No units found for the landlord. Run `php artisan migrate --seed` first.');

            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));
        $sync = (bool) $this->option('sync');
        $profiles = self::profiles();
        $keys = array_keys($profiles);

        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $profileKey = $keys[$i % count($keys)];
            $unit = $units[$i % $units->count()];
            $answers = self::buildAnswers($dir, $profileKey, $profiles[$profileKey], $i);

            $link = ApplicationLink::factory()->create([
                'unit_id' => $unit->id,
                'label' => "Sample · {$profileKey}",
                'is_accepting' => true,
            ]);

            $application = $service->createApplication($link, $answers, null);

            if ($sync) {
                ScoreApplication::dispatchSync($application);
            } else {
                $service->requestScore($application);
            }

            $rows[] = [$profileKey, "#{$application->id}", "{$answers['first_name']} {$answers['last_name']}", $unit->label];
        }

        $this->table(['Profile', 'Application', 'Applicant', 'Unit'], $rows);
        $this->info($count.' application'.($count === 1 ? '' : 's').' created and '
            .($sync ? 'scored.' : 'queued for scoring.'));

        if (! $sync) {
            $this->comment('Run `php artisan queue:work` to process the scoring jobs.');
        }

        return self::SUCCESS;
    }

    /**
     * Assemble one application's answers: the profile's answer template, an
     * identity (suffixed when a profile repeats so each applicant is distinct),
     * and the profile's document set as uploaded files.
     *
     * Public and static so the `screening:eval-prompt` harness can build the exact
     * same answer set (documents included) through the same code path.
     *
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    public static function buildAnswers(string $dir, string $profileKey, array $profile, int $index): array
    {
        $photoMime = $profile['_photo_mime'];
        unset($profile['_photo_mime']);

        // Distinguish repeats of the same profile (count > number of profiles).
        $round = intdiv($index, count(self::profiles()));
        $suffix = $round > 0 ? "+{$round}" : '';
        [$local, $domain] = explode('@', $profile['email']);

        $file = fn (string $folder, string $name, string $mime = 'application/pdf'): UploadedFile => new UploadedFile("{$dir}/{$folder}/{$name}", $name, $mime, null, true);

        return array_merge($profile, [
            'email' => "{$local}{$suffix}@{$domain}",
            'phone' => '(614) 555-0'.str_pad((string) (100 + $index), 3, '0', STR_PAD_LEFT),
            'pay_stubs' => $file($profileKey, '01-pay-stub.pdf'),
            'photo_id' => $photoMime === 'image/png'
                ? $file('unreadable', 'photo-id-scan.png', 'image/png')
                : $file($profileKey, '02-photo-id.pdf'),
            'proof_of_income' => $file($profileKey, '03-employment-letter.pdf'),
            'credit_report' => $file($profileKey, '04-credit-report.pdf'),
        ]);
    }

    /**
     * The sample applicant profiles, keyed by the fixture folder whose documents
     * back them. The answers mirror each profile's documents so the scoring
     * service has consistent (or deliberately inconsistent) signal to grade.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function profiles(): array
    {
        return [
            'strong' => [
                'first_name' => 'Jordan', 'last_name' => 'Mitchell',
                'email' => 'jordan.mitchell@example.com', 'date_of_birth' => '1989-07-22',
                'current_address' => '47 Birchwood Terrace, Apt 2, Columbus, OH 43215',
                'employer_name' => 'Northwind Logistics Inc.', 'job_title' => 'Senior Operations Analyst',
                'employment_type' => 'Full-time', 'gross_monthly_income' => 8062, 'employment_start_date' => '2021-03-14',
                'desired_move_in_date' => '2026-08-01', 'number_of_occupants' => 2,
                'has_pets' => false, 'is_smoker' => false,
                'drivers_license_number' => 'D182-4471-8820', 'credit_score_range' => 'Excellent (750+)',
                'ever_evicted' => false, 'screening_consent' => true, '_photo_mime' => 'application/pdf',
            ],
            'borderline' => [
                'first_name' => 'Alex', 'last_name' => 'Rivera',
                'email' => 'alex.rivera@example.com', 'date_of_birth' => '1996-03-11',
                'current_address' => '218 Maple Street, Unit B, Columbus, OH 43201',
                'employer_name' => 'BrightMart Retail', 'job_title' => 'Shift Supervisor',
                'employment_type' => 'Full-time', 'gross_monthly_income' => 3791, 'employment_start_date' => '2026-02-24',
                'desired_move_in_date' => '2026-08-01', 'number_of_occupants' => 2,
                'has_pets' => true, 'pet_details' => 'One cat, 1 year old', 'is_smoker' => false,
                'drivers_license_number' => 'R556-9013-2245', 'credit_score_range' => 'Fair (650–699)',
                'ever_evicted' => false, 'screening_consent' => true, '_photo_mime' => 'application/pdf',
            ],
            'redflag' => [
                'first_name' => 'Sam', 'last_name' => 'Carter',
                'email' => 'sam.carter@example.com', 'date_of_birth' => '1994-11-02',
                'current_address' => '903 Hollis Ave, Columbus, OH 43205',
                'employer_name' => 'Self-employed / rideshare (RideNow, DashGo)', 'job_title' => 'Independent contractor',
                'employment_type' => 'Self-employed', 'gross_monthly_income' => 2050, 'employment_start_date' => null,
                'desired_move_in_date' => '2026-08-01', 'number_of_occupants' => 1,
                'has_pets' => false, 'is_smoker' => true,
                'drivers_license_number' => 'C771-2204-9930', 'credit_score_range' => 'Poor (below 650)',
                'ever_evicted' => true, 'eviction_details' => 'Evicted in 2023 after a job loss; balance since paid.',
                'screening_consent' => true, '_photo_mime' => 'image/png',
            ],
        ];
    }
}
