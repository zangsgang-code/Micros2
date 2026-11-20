<?php

namespace App\Support;

class CareerGoalCatalog
{
    /**
     * @var list<string>
     */
    private const CAREER_GOALS = [
        'Software Engineer',
        'Web Developer',
        'Front-End Developer',
        'Back-End Developer',
        'Full-Stack Developer',
        'Cybersecurity Specialist',
        'Information Security Analyst',
        'Network Engineer',
        'IT Support Specialist',
        'Cloud Engineer',
        'Database Administrator',
        'Data Analyst',
        'Data Engineer',
        'Data Scientist',
        'Teacher',
        'Instructional Designer',
        'Education Administrator',
        'Nurse',
        'Medical Laboratory Scientist',
        'Biologist',
        'Nutritionist',
        'Dietitian',
        'Public Health Specialist',
        'Accountant',
        'Financial Analyst',
        'Economist',
        'Business Analyst',
        'Entrepreneur',
        'Civil Engineer',
        'Graphic Designer',
        'UI/UX Designer',
        'Journalist',
        'Technical Writer',
        'Public Administrator',
        'Policy Analyst',
        'Operations Manager',
        'Project Manager',
        'Supply Chain Analyst',
        'Vocational Instructor',
        'Industrial Technician',
        'Automotive Technician',
        'Electrician',
        'Welder',
        'Chef',
        'Hospitality Manager',
        'Event Planner',
        'Social Worker',
        'Counselor',
        'Community Development Worker',
        'Other',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const CAREER_GOALS_BY_SKILL_GROUP = [
        'Language & Communication' => [
            'Teacher',
            'Journalist',
            'Technical Writer',
            'Graphic Designer',
            'Social Worker',
        ],
        'Economics' => [
            'Economist',
            'Financial Analyst',
            'Data Analyst',
            'Accountant',
            'Business Analyst',
            'Entrepreneur',
        ],
        'Computer Science' => [
            'Software Engineer',
            'Web Developer',
            'Data Analyst',
            'Data Engineer',
            'Data Scientist',
            'Business Analyst',
        ],
        'Information Technology' => [
            'Web Developer',
            'Cybersecurity Specialist',
            'Information Security Analyst',
            'Network Engineer',
            'IT Support Specialist',
            'Cloud Engineer',
            'Database Administrator',
            'Software Engineer',
        ],
        'Mathematics' => [
            'Data Analyst',
            'Data Scientist',
            'Data Engineer',
            'Accountant',
            'Financial Analyst',
            'Civil Engineer',
            'Software Engineer',
        ],
        'Biology' => [
            'Nurse',
            'Medical Laboratory Scientist',
            'Biologist',
            'Nutritionist',
            'Dietitian',
            'Public Health Specialist',
        ],
        'Public Administration' => [
            'Public Administrator',
            'Policy Analyst',
            'Project Manager',
            'Social Worker',
            'Community Development Worker',
        ],
        'Operations Management' => [
            'Operations Manager',
            'Project Manager',
            'Supply Chain Analyst',
            'Business Analyst',
            'Entrepreneur',
        ],
        'Finance' => [
            'Accountant',
            'Financial Analyst',
            'Economist',
            'Data Analyst',
            'Entrepreneur',
        ],
        'Secondary Education' => [
            'Teacher',
            'Instructional Designer',
            'Education Administrator',
            'Counselor',
        ],
        'Technology & Livelihood Education' => [
            'Teacher',
            'Chef',
            'Entrepreneur',
            'Graphic Designer',
            'Industrial Technician',
        ],
        'Technical-Vocational' => [
            'Vocational Instructor',
            'Industrial Technician',
            'Automotive Technician',
            'Electrician',
            'Welder',
        ],
        'Industrial Technology' => [
            'Industrial Technician',
            'Automotive Technician',
            'Civil Engineer',
            'Electrician',
        ],
        'Hospitality & Tourism' => [
            'Chef',
            'Hospitality Manager',
            'Event Planner',
            'Entrepreneur',
        ],
        'Nutrition & Dietetics' => [
            'Nurse',
            'Nutritionist',
            'Dietitian',
            'Public Health Specialist',
        ],
        'Social Work' => [
            'Social Worker',
            'Counselor',
            'Community Development Worker',
            'Public Health Specialist',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const CAREER_GOALS_BY_SKILL = [
        'Web Development' => [
            'Web Developer',
            'Front-End Developer',
            'Back-End Developer',
            'Full-Stack Developer',
            'Software Engineer',
            'UI/UX Designer',
        ],
        'Cybersecurity' => [
            'Cybersecurity Specialist',
            'Information Security Analyst',
            'Network Engineer',
        ],
        'Databases' => [
            'Database Administrator',
            'Data Analyst',
            'Data Engineer',
            'Software Engineer',
        ],
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::CAREER_GOALS;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function bySkill(): array
    {
        $careerGoalsBySkill = [];

        foreach (SkillCatalog::groups() as $group => $skills) {
            foreach ($skills as $skill) {
                $careerGoalsBySkill[$skill] = self::CAREER_GOALS_BY_SKILL_GROUP[$group] ?? [];
            }
        }

        return array_replace($careerGoalsBySkill, self::CAREER_GOALS_BY_SKILL);
    }

    /**
     * @param  list<string>  $skills
     * @return list<string>
     */
    public static function forSkills(array $skills): array
    {
        $careerGoalsBySkill = self::bySkill();
        $relatedGoals = [];

        foreach ($skills as $skill) {
            foreach ($careerGoalsBySkill[$skill] ?? [] as $careerGoal) {
                $relatedGoals[$careerGoal] = true;
            }
        }

        return array_values(array_filter(
            self::CAREER_GOALS,
            static fn (string $careerGoal): bool => $careerGoal === 'Other' || isset($relatedGoals[$careerGoal]),
        ));
    }
}
