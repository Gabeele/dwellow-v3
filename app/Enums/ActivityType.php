<?php

namespace App\Enums;

/**
 * The kinds of activity recorded on an application's timeline. The value is
 * stored; the label is for display and the {@see self::isSystem()} flag marks
 * events performed by dwellow itself (no human causer) so the UI can attribute
 * them to "Dwellow AI" rather than a person.
 */
enum ActivityType: string
{
    case Submitted = 'submitted';
    case AnalysisStarted = 'analysis_started';
    case AnalysisCompleted = 'analysis_completed';
    case AnalysisFailed = 'analysis_failed';
    case MarkedReviewing = 'marked_reviewing';
    case StatusChanged = 'status_changed';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::AnalysisStarted => 'Analysis started',
            self::AnalysisCompleted => 'Analysis completed',
            self::AnalysisFailed => 'Analysis failed',
            self::MarkedReviewing => 'Marked reviewing',
            self::StatusChanged => 'Status changed',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Whether dwellow performed this event itself (no human causer), so the UI
     * attributes it to the system / AI rather than a person.
     */
    public function isSystem(): bool
    {
        return match ($this) {
            self::AnalysisStarted,
            self::AnalysisCompleted,
            self::AnalysisFailed => true,
            default => false,
        };
    }
}
