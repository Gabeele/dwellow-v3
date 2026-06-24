<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the public marketing surface: the landing page plus the Pricing,
 * Docs and Roadmap pages. Page content lives here (not in the Vue files) so it
 * ships in the initial server response — good for SEO and crawlers — and stays
 * testable.
 */
class MarketingController extends Controller
{
    /**
     * The landing page. Authenticated users go straight to their dashboard.
     */
    public function home(): Response|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Welcome', [
            'seo' => $this->seo(
                'Dwellow — Tenant screening for small landlords',
                'Dwellow turns every rental application into one comparable Score — reading documents, checking references, and ranking applicants against your criteria. No bureau accounts, no spreadsheets.',
                route('home'),
            ),
            'stats' => [
                [
                    'value' => '$3,500+',
                    'label' => 'Cost of one eviction',
                    'detail' => 'Lost rent, legal fees and turnover from a single wrong call.',
                ],
                [
                    'value' => '2–3 mo',
                    'label' => 'Rent lost to a bad fit',
                    'detail' => 'Vacancy plus the months it takes to recover and re-list.',
                ],
                [
                    'value' => '1 Score',
                    'label' => 'Same yardstick, every applicant',
                    'detail' => 'No gut feel, no apples-to-oranges, no spreadsheet gymnastics.',
                ],
            ],
            'steps' => [
                [
                    'title' => 'Add your property',
                    'description' => 'List a property and its units in a couple of minutes.',
                ],
                [
                    'title' => 'Build the application',
                    'description' => 'Customize a form for each unit — ask exactly what you need.',
                ],
                [
                    'title' => 'Share one link',
                    'description' => 'Applicants apply and upload documents — no account required.',
                ],
                [
                    'title' => 'Let AI do the legwork',
                    'description' => 'Dwellow reads every submission, emails references, and scores it.',
                ],
                [
                    'title' => 'Compare and decide',
                    'description' => 'Review applicants side by side and pick the right tenant.',
                ],
            ],
            'features' => [
                [
                    'title' => 'Document-based, not bureau-based',
                    'description' => 'Applicants provide their own documents, so you skip credit-bureau accounts and the compliance overhead.',
                ],
                [
                    'title' => 'References, handled',
                    'description' => 'Dwellow emails references for you and folds their responses into the Score.',
                ],
                [
                    'title' => 'One Score, easy to compare',
                    'description' => 'Every applicant gets a consistent Score against your criteria — no gut feel, no apples to oranges.',
                ],
            ],
            'comparison' => [
                'columns' => ['Dwellow', 'Spreadsheets & email', 'Legacy screening'],
                'rows' => [
                    [
                        'capability' => 'One comparable Score per applicant',
                        'cells' => ['yes', 'no', 'partial'],
                    ],
                    [
                        'capability' => 'Reads pay stubs & documents for you',
                        'cells' => ['yes', 'no', 'no'],
                    ],
                    [
                        'capability' => 'Emails and tracks references automatically',
                        'cells' => ['yes', 'no', 'no'],
                    ],
                    [
                        'capability' => 'No applicant account or login required',
                        'cells' => ['yes', 'partial', 'no'],
                    ],
                    [
                        'capability' => 'No credit-bureau account to maintain',
                        'cells' => ['yes', 'yes', 'no'],
                    ],
                    [
                        'capability' => 'Custom questions per unit',
                        'cells' => ['yes', 'partial', 'no'],
                    ],
                    [
                        'capability' => 'Decision you can defend later',
                        'cells' => ['yes', 'no', 'partial'],
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Do I need a credit-bureau account to use Dwellow?',
                    'answer' => 'No. Dwellow is document-based — applicants provide their own pay stubs, references and supporting documents, and Dwellow reads and verifies them. That means no bureau contracts, no per-pull fees and far less compliance overhead. Verified bureau checks are on the roadmap as an optional add-on.',
                ],
                [
                    'question' => 'What is the Score, exactly?',
                    'answer' => 'The Score is a single 0–100 number that summarizes how well an applicant matches the criteria you set — income, rental history, references and the documents they submitted. It is always shown with the evidence behind it, so you can see why an applicant scored the way they did, not just the number.',
                ],
                [
                    'question' => 'Do applicants need to create an account?',
                    'answer' => 'No. You share one link per unit. Applicants fill out the form and upload documents without signing up for anything, which dramatically increases completion rates compared to portals that force a login.',
                ],
                [
                    'question' => 'Is this only for small landlords?',
                    'answer' => 'Dwellow is built for landlords with 1–20 units, but the same workflow scales to property managers and larger portfolios. If you manage at scale and need SSO, API access or bulk onboarding, our Enterprise plan covers it.',
                ],
                [
                    'question' => 'Is my applicants\' data secure?',
                    'answer' => 'Yes. Documents are stored privately and access is scoped to you. Applicant links are unguessable tokens, public endpoints are rate-limited, and you control how long data is retained.',
                ],
            ],
            'roadmap' => $this->roadmapPhases(),
        ]);
    }

    /**
     * Pricing — honest to the product's beta stage: everything is free today;
     * these are the plans Dwellow will launch with.
     */
    public function pricing(): Response
    {
        return Inertia::render('marketing/Pricing', [
            'seo' => $this->seo(
                'Pricing — Dwellow',
                'Simple, honest pricing for tenant screening. Free while Dwellow is in beta, with plans for active landlords and property managers at launch.',
                route('pricing'),
            ),
            'plans' => [
                [
                    'name' => 'Starter',
                    'price' => '$0',
                    'cadence' => 'free in beta',
                    'tagline' => 'Everything you need to screen your next vacancy.',
                    'cta' => ['label' => 'Start screening', 'href' => '/register'],
                    'highlighted' => false,
                    'features' => [
                        'Up to 2 properties',
                        'Unlimited application links',
                        'AI document reading & scoring',
                        'Automated reference requests',
                        'Compare-and-decide dashboard',
                    ],
                ],
                [
                    'name' => 'Pro',
                    'price' => '$29',
                    'cadence' => 'per month, at launch',
                    'tagline' => 'For active landlords filling units all year.',
                    'cta' => ['label' => 'Start free, upgrade later', 'href' => '/register'],
                    'highlighted' => true,
                    'badge' => 'Most popular',
                    'features' => [
                        'Everything in Starter',
                        'Unlimited properties & units',
                        'Reusable application templates',
                        'Portfolio-wide applicant view',
                        'Optional verified bureau checks',
                        'Priority support',
                    ],
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 'Custom',
                    'cadence' => 'for teams & property managers',
                    'tagline' => 'For PM companies and larger portfolios.',
                    'cta' => ['label' => 'Talk to us', 'href' => 'mailto:hello@dwellow.app'],
                    'highlighted' => false,
                    'anchor' => 'enterprise',
                    'features' => [
                        'Everything in Pro',
                        'SSO & role-based access',
                        'API access & bulk imports',
                        'Dedicated onboarding',
                        'Custom data-retention policies',
                        'SLA & priority routing',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'Is Dwellow really free right now?',
                    'answer' => 'Yes. While we are in beta, screening is free — AI scoring, reference checks and the full dashboard included. The plans above are what we will launch with, so you can plan ahead. Beta accounts get plenty of notice before anything changes.',
                ],
                [
                    'question' => 'Who pays — me or the applicant?',
                    'answer' => 'You do. We do not charge applicants to apply, because friction and fees hurt completion rates and quietly screen out good tenants. Pricing is on the landlord side, where the value is.',
                ],
                [
                    'question' => 'Can I change plans later?',
                    'answer' => 'Yes. Start on Starter, move to Pro when you are filling units regularly, and talk to us about Enterprise when you are managing at scale. Your properties, links and history come with you.',
                ],
                [
                    'question' => 'What counts as a property?',
                    'answer' => 'A property is a building or address; each can hold multiple units, and each unit gets its own application form and link. The Starter plan covers up to two properties with unlimited units inside them.',
                ],
            ],
        ]);
    }

    /**
     * Docs — a real getting-started and concepts guide. Content-rich on
     * purpose: it answers the questions prospects actually search for.
     */
    public function docs(): Response
    {
        return Inertia::render('marketing/Docs', [
            'seo' => $this->seo(
                'Docs — How Dwellow tenant screening works',
                'A practical guide to screening tenants with Dwellow: how the Score works, setting up properties and application links, automated references, and making a defensible decision.',
                route('docs'),
            ),
            'intro' => 'Four short guides take you from an empty account to a scored shortlist. Each one mirrors exactly what you\'ll see on screen — follow along in your own dashboard.',
            'guides' => [
                [
                    'id' => 'add-property',
                    'title' => 'Add a property',
                    'eyebrow' => 'Guide 01',
                    'intro' => 'A property is a building or address you manage. Everything else — units, application links, applicants — hangs off a property, so this is always step one.',
                    'image' => '/images/docs/add-property.png',
                    'imageAlt' => 'The Dwellow new-property form with name and address fields',
                    'markers' => [
                        ['n' => 1, 'x' => 6, 'y' => 14, 'label' => 'Open Properties in the sidebar to start.'],
                        ['n' => 2, 'x' => 59, 'y' => 31, 'label' => 'Give the property a name and address.'],
                        ['n' => 3, 'x' => 49, 'y' => 80, 'label' => 'Pick single-home or multi-unit, then save.'],
                    ],
                    'steps' => [
                        'From the dashboard, open Properties and click New property.',
                        'Enter the property name and address.',
                        'Choose whether it\'s a single home or holds multiple units.',
                        'Save — you\'ll land on the property page, ready to add units.',
                    ],
                ],
                [
                    'id' => 'add-unit',
                    'title' => 'Add a unit',
                    'eyebrow' => 'Guide 02',
                    'intro' => 'Units are the individual spaces inside a property. Each unit gets its own application form and its own link, so a studio and a four-bedroom can ask completely different questions.',
                    'image' => '/images/docs/add-unit.png',
                    'imageAlt' => 'The Dwellow add-unit form showing unit name and rent',
                    'markers' => [
                        ['n' => 1, 'x' => 59, 'y' => 31, 'label' => 'Give the unit a label like "Unit 2".'],
                        ['n' => 2, 'x' => 72, 'y' => 41, 'label' => 'Set bedrooms, baths and the monthly rent.'],
                        ['n' => 3, 'x' => 51, 'y' => 51, 'label' => 'Mark it available, then Add unit.'],
                    ],
                    'steps' => [
                        'Open a property and click Add unit.',
                        'Name the unit (e.g. Unit 2, or Basement suite) and set the rent.',
                        'Mark it available.',
                        'Save — the unit now has its own application form.',
                    ],
                ],
                [
                    'id' => 'open-screening',
                    'title' => 'Open screening & share the link',
                    'eyebrow' => 'Guide 03',
                    'intro' => 'Screening starts the moment you switch a unit on. Each unit has its own link, so applicants apply with no account, no password, no friction. You can fine-tune the questions any time from the unit\'s application form.',
                    'image' => '/images/docs/property-show.png',
                    'imageAlt' => 'A property page listing each unit with an on/off Screening link control',
                    'markers' => [
                        ['n' => 1, 'x' => 77, 'y' => 51, 'label' => 'Every unit has its own Screening control.'],
                        ['n' => 2, 'x' => 78, 'y' => 57, 'label' => 'Toggle it On to start taking applications.'],
                        ['n' => 3, 'x' => 72, 'y' => 57, 'label' => 'Copy the shareable link right from here.'],
                    ],
                    'steps' => [
                        'Open a property to see its units and their screening status.',
                        'Flip a unit\'s Screening toggle On to open applications.',
                        'Copy that unit\'s application link from the same control.',
                        'Paste it on your listing, in a text, or an email — that\'s it.',
                    ],
                ],
                [
                    'id' => 'view-applicants',
                    'title' => 'View & decide on applicants',
                    'eyebrow' => 'Guide 04',
                    'intro' => 'As applications arrive, Dwellow reads them and assigns each a Score. Review everyone side by side, open the evidence behind any Score, then approve or decline.',
                    'image' => '/images/docs/applicant-show.png',
                    'imageAlt' => 'An applicant detail page with a fit score, evidence cards and an approve or decline panel',
                    'markers' => [
                        ['n' => 1, 'x' => 26, 'y' => 29, 'label' => 'Each applicant gets a fit score.'],
                        ['n' => 2, 'x' => 34, 'y' => 66, 'label' => 'The evidence behind it: income, credit, employment, ID.'],
                        ['n' => 3, 'x' => 80, 'y' => 28, 'label' => 'Approve or decline — Dwellow emails them for you.'],
                    ],
                    'steps' => [
                        'Open a unit\'s applicants to see everyone who applied, side by side.',
                        'Open any applicant to see their fit score.',
                        'Read the evidence behind it — income, credit, employment and ID.',
                        'Approve or decline; Dwellow emails them automatically.',
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'How long does it take to set up?',
                    'answer' => 'Most landlords add a property, tailor a unit\'s application and share the link in under ten minutes. Scoring and references happen automatically after that.',
                ],
                [
                    'question' => 'Can I customize the application for each unit?',
                    'answer' => 'Yes. Every unit has its own application form, so you can ask exactly what is relevant for that space — and reuse templates once you find a set of questions you like.',
                ],
                [
                    'question' => 'What documents can applicants upload?',
                    'answer' => 'Whatever you ask for — typically pay stubs, ID and references. Dwellow reads them and uses them to verify income and build the Score.',
                ],
            ],
        ]);
    }

    /**
     * Roadmap — where Dwellow is headed, plus what has already shipped.
     */
    public function roadmap(): Response
    {
        return Inertia::render('marketing/Roadmap', [
            'seo' => $this->seo(
                'Roadmap — Dwellow',
                'Where Dwellow is headed: best-in-class screening first, then the full rental lifecycle — leases, rent collection, maintenance and accounting.',
                route('roadmap'),
            ),
            'groups' => [
                [
                    'label' => 'Shipped',
                    'caption' => 'Live in Dwellow today — real, in landlords\' hands.',
                    'status' => 'shipped',
                    'items' => [
                        ['title' => 'Custom application forms per unit', 'description' => 'Tailor exactly what each unit asks applicants.'],
                        ['title' => 'Link-only applicants', 'description' => 'Share one link — applicants apply with no account or login.'],
                        ['title' => 'AI document reading & scoring', 'description' => 'Dwellow reads submissions and turns them into one comparable Score.'],
                        ['title' => 'Automated reference requests', 'description' => 'References are emailed, chased and folded into the Score for you.'],
                        ['title' => 'Compare-and-decide dashboard', 'description' => 'Every applicant side by side, sorted by Score.'],
                        ['title' => 'Branded approval & decline emails', 'description' => 'Applicants always hear back, in your name.'],
                    ],
                ],
                [
                    'label' => 'In progress',
                    'caption' => 'What we\'re building right now.',
                    'status' => 'now',
                    'items' => [
                        ['title' => 'Reusable application templates', 'description' => 'Save a set of questions once and apply it to any unit.'],
                        ['title' => 'Portfolio-wide applicant view', 'description' => 'See and compare applicants across every property at once.'],
                    ],
                ],
                [
                    'label' => 'Next up',
                    'caption' => 'On deck after the current work.',
                    'status' => 'next',
                    'items' => [
                        ['title' => 'Optional verified bureau checks', 'description' => 'Add a formal credit check when you want one — still no account to maintain.'],
                        ['title' => 'Landlord subscriptions & billing', 'description' => 'The Pro and Enterprise plans, with self-serve billing.'],
                    ],
                ],
                [
                    'label' => 'Later',
                    'caption' => 'The full rental lifecycle, once screening is best-in-class.',
                    'status' => 'later',
                    'items' => [
                        ['title' => 'Leases & onboarding', 'description' => 'Turn an approved applicant into a signed tenant without leaving Dwellow.'],
                        ['title' => 'Online rent collection', 'description' => 'Collect and track rent per unit.'],
                        ['title' => 'Maintenance requests', 'description' => 'Let tenants report issues and track them to done.'],
                        ['title' => 'Per-property accounting', 'description' => 'Income, expenses and reporting for each property.'],
                    ],
                ],
            ],
            'faq' => [
                [
                    'question' => 'How do you decide what to build next?',
                    'answer' => 'We prioritize the things that make screening decisions more confident and the things landlords ask for most. Screening depth comes before lifecycle breadth.',
                ],
                [
                    'question' => 'Will pricing change as you add features?',
                    'answer' => 'Dwellow is free during beta. When we launch paid plans, beta users get notice and the screening workflow you rely on stays available.',
                ],
                [
                    'question' => 'Can I request a feature?',
                    'answer' => 'Please do. Email hello@dwellow.app — landlord feedback is the single biggest input into this roadmap.',
                ],
            ],
        ]);
    }

    /**
     * Shared roadmap phases, used on both the landing page and the roadmap page.
     *
     * @return array<int, array{phase: string, title: string, current: bool, items: array<int, string>}>
     */
    private function roadmapPhases(): array
    {
        return [
            [
                'phase' => 'Now',
                'title' => 'Tenant screening',
                'current' => true,
                'items' => [
                    'Custom application forms per unit',
                    'Link-only applicants, no accounts',
                    'Automated references and AI scoring',
                    'Compare-and-decide dashboard',
                ],
            ],
            [
                'phase' => 'Next',
                'title' => 'Best-in-class screening',
                'current' => false,
                'items' => [
                    'Reusable form templates',
                    'Portfolio-wide applicant view',
                    'Optional verified bureau checks',
                    'Landlord subscriptions',
                ],
            ],
            [
                'phase' => 'Later',
                'title' => 'The full rental lifecycle',
                'current' => false,
                'items' => [
                    'Leases and onboarding',
                    'Online rent collection',
                    'Maintenance requests',
                    'Per-property accounting',
                ],
            ],
        ];
    }

    /**
     * Build the shared SEO payload consumed by app.blade.php.
     *
     * @return array{title: string, description: string, url: string, image: string}
     */
    private function seo(string $title, string $description, string $url): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image' => asset('apple-touch-icon.png'),
        ];
    }
}
