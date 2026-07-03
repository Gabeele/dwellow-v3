<?php

namespace App\Enums;

use App\Onboarding\OnboardingState;

/**
 * The single unfinished step standing between a landlord and their first
 * scored applicant, as computed by {@see OnboardingState}.
 */
enum OnboardingStep: string
{
    case AddProperty = 'add_property';
    case BuildApplication = 'build_application';
    case ShareLink = 'share_link';
    case Activated = 'activated';
}
