<?php

namespace App\Blog\Enums;

enum PostCategory: string
{
    case AI_TRENDS = 'ai_trends';
    case CAREER_ADVICE = 'career_advice';
    case INTERVIEW_PREP = 'interview_prep';
    case INDUSTRY_INSIGHTS = 'industry_insights';
    case TECHNOLOGY_UPDATES = 'technology_updates';
    case SKILL_DEVELOPMENT = 'skill_development';
    case ETHICAL_CONSIDERATIONS = 'ethical_considerations';
    case CASE_STUDIES = 'case_studies';
    case EVENT_COVERAGE = 'event_coverage';
    case OPINION_PIECES = 'opinion_pieces';
    
    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }
    
    public function label(): string
    {
        return match ($this) {
            self::AI_TRENDS => 'AI Trends',
            self::CAREER_ADVICE => 'Career Advice',
            self::INTERVIEW_PREP => 'Interview Preparation',
            self::INDUSTRY_INSIGHTS => 'Industry Insights',
            self::TECHNOLOGY_UPDATES => 'Technology Updates',
            self::SKILL_DEVELOPMENT => 'Skill Development',
            self::ETHICAL_CONSIDERATIONS => 'Ethical Considerations',
            self::CASE_STUDIES => 'Case Studies',
            self::EVENT_COVERAGE => 'Event Coverage',
            self::OPINION_PIECES => 'Opinion Pieces',
        };
    }
}
