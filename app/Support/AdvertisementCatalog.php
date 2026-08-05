<?php

namespace App\Support;

/**
 * Canonical advertisement types, pages, positions, and banner size recommendations.
 *
 * Layout blocks mirror the live frontend page structure so the admin placement
 * preview stays accurate while frontend slot rendering remains a later phase.
 */
final class AdvertisementCatalog
{
    public const TYPE_BANNER = 'banner';

    public const TYPE_IFRAME = 'iframe';

    public const TYPE_HTML = 'html';

    public const SOURCE_GOOGLE = 'google';

    public const SOURCE_CUSTOM = 'custom';

    /**
     * Positions that accept multiple advertisements.
     *
     * @var list<string>
     */
    public const MULTI_POSITIONS = [
        'left_sidebar',
        'right_sidebar',
        'between_sections',
        'below_items',
        'before_h2',
        'after_header',
        'after_hero',
        'after_stats',
        'after_featured_exams',
        'after_questions',
        'after_categories',
        'after_blogs',
        'after_news',
        'after_testimonials',
        'after_faqs',
        'after_newsletter',
        'after_cta',
        'after_filters',
        'after_content',
        'above_footer',
        'below_content',
        'before_content',
        'after_about',
        'after_details',
        'after_related',
        'after_results',
        'after_share',
        'after_attempts',
        'after_feedback',
        'after_form',
        'after_map',
        'after_mission',
        'after_offers',
        'after_why',
        'after_timeline',
        'after_values',
        'after_legal_sections',
        'after_explanation',
        'after_options',
    ];

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_BANNER => 'Banner Image',
            self::TYPE_IFRAME => 'Iframe URL',
            self::TYPE_HTML => 'HTML Code',
        ];
    }

    /**
     * @return array<string, array{label: string, width: int, height: int, note: string}>
     */
    public static function bannerSizes(): array
    {
        return [
            'leaderboard' => [
                'label' => 'Leaderboard',
                'width' => 728,
                'height' => 90,
                'note' => 'Best above the page title or above the footer on desktop detail and listing pages.',
            ],
            'large_leaderboard' => [
                'label' => 'Large Leaderboard',
                'width' => 970,
                'height' => 90,
                'note' => 'Wide desktop headers and premium top-of-page placements.',
            ],
            'medium_rectangle' => [
                'label' => 'Medium Rectangle',
                'width' => 300,
                'height' => 250,
                'note' => 'Ideal for sidebars and between content sections.',
            ],
            'large_rectangle' => [
                'label' => 'Large Rectangle',
                'width' => 336,
                'height' => 280,
                'note' => 'High-visibility in-content or sidebar placements.',
            ],
            'half_page' => [
                'label' => 'Half Page',
                'width' => 300,
                'height' => 600,
                'note' => 'Tall sidebars on long-form detail pages.',
            ],
            'wide_skyscraper' => [
                'label' => 'Wide Skyscraper',
                'width' => 160,
                'height' => 600,
                'note' => 'Narrow left/right sidebars on desktop layouts.',
            ],
            'mobile_banner' => [
                'label' => 'Mobile Banner',
                'width' => 320,
                'height' => 100,
                'note' => 'Recommended for mobile-first placements above or below titles.',
            ],
            'large_mobile_banner' => [
                'label' => 'Large Mobile Banner',
                'width' => 320,
                'height' => 50,
                'note' => 'Compact sticky-friendly mobile banners.',
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function homeLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Hero', 'skeleton' => 'hero', 'after' => 'after_hero'],
            ['id' => 'stats', 'label' => 'Stats', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'featured_exams', 'label' => 'Featured exams', 'skeleton' => 'cards4', 'after' => 'after_featured_exams'],
            ['id' => 'questions', 'label' => 'Questions', 'skeleton' => 'cards3', 'after' => 'after_questions'],
            ['id' => 'categories', 'label' => 'Categories', 'skeleton' => 'chips', 'after' => 'after_categories'],
            ['id' => 'blogs', 'label' => 'Blogs', 'skeleton' => 'cards3', 'after' => 'after_blogs'],
            ['id' => 'news', 'label' => 'News', 'skeleton' => 'cards3', 'after' => 'after_news'],
            ['id' => 'testimonials', 'label' => 'Testimonials', 'skeleton' => 'quotes', 'after' => 'after_testimonials'],
            ['id' => 'faqs', 'label' => 'FAQs', 'skeleton' => 'faq', 'after' => 'after_faqs'],
            // Newsletter + CTA bands exist in Blade but are temporarily commented out on the live home page.
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'after' => null, 'chrome' => true],
        ];
    }

    /**
     * Shared listing chrome: hero + filter toolbar, results grid, load more.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function listingLayout(string $heroLabel = 'Listing'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => $heroLabel, 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'filters', 'label' => 'Filter toolbar', 'skeleton' => 'filters', 'after' => 'after_filters'],
            ['id' => 'items', 'label' => 'Results grid', 'skeleton' => 'cards3', 'after' => 'below_items'],
            ['id' => 'load_more', 'label' => 'Load more', 'skeleton' => 'load_more', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Blog / news detail — matches redesigned article + aside layout.
     * Insert lines before the page H1 and before each in-article H2.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function articleDetailLayout(string $title = 'Article', string $relatedLabel = 'Related content'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'title', 'label' => $title.' (H1)', 'skeleton' => 'article_title', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'banner', 'label' => 'Featured image', 'skeleton' => 'banner', 'after' => 'before_content'],
            ['id' => 'lead', 'label' => 'Lead / excerpt', 'skeleton' => 'section', 'after' => null],
            ['id' => 'toc', 'label' => 'Table of contents', 'skeleton' => 'faq', 'after' => null],
            ['id' => 'h2_a', 'label' => 'Section heading (H2)', 'skeleton' => 'heading_h2', 'before' => 'before_h2'],
            ['id' => 'prose_a', 'label' => 'Article body', 'skeleton' => 'prose', 'after' => null],
            ['id' => 'h2_b', 'label' => 'Section heading (H2)', 'skeleton' => 'heading_h2', 'before' => 'before_h2'],
            ['id' => 'prose_b', 'label' => 'Article body continued', 'skeleton' => 'prose', 'after' => 'between_sections'],
            ['id' => 'share', 'label' => 'Share panel', 'skeleton' => 'section', 'after' => 'after_share'],
            ['id' => 'related', 'label' => $relatedLabel, 'skeleton' => 'cards3', 'after' => 'after_related'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function blogArticleLayout(): array
    {
        return self::articleDetailLayout('Blog article', 'Related blogs');
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function newsArticleLayout(): array
    {
        return self::articleDetailLayout('News article', 'More news');
    }

    /**
     * Exam detail — overview, details, attempts, feedback, share + action aside.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Exam title (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'about', 'label' => 'About this exam', 'skeleton' => 'prose', 'after' => 'after_about'],
            ['id' => 'details', 'label' => 'Exam details', 'skeleton' => 'stats', 'after' => 'after_details'],
            ['id' => 'attempts', 'label' => 'Previous attempts', 'skeleton' => 'section', 'after' => 'after_attempts'],
            ['id' => 'feedback', 'label' => 'Feedback', 'skeleton' => 'section', 'after' => 'after_feedback'],
            ['id' => 'share', 'label' => 'Share panel', 'skeleton' => 'section', 'after' => 'after_share'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Question practice detail — question, options, explanation, share, related.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function questionDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'top', 'label' => 'Practice header (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'panel', 'label' => 'Question panel', 'skeleton' => 'prose', 'after' => 'before_content'],
            ['id' => 'options', 'label' => 'Options', 'skeleton' => 'faq', 'after' => 'after_options'],
            ['id' => 'explain', 'label' => 'Explanation', 'skeleton' => 'section', 'after' => 'after_explanation'],
            ['id' => 'share', 'label' => 'Share panel', 'skeleton' => 'section', 'after' => 'after_share'],
            ['id' => 'related', 'label' => 'Related blogs', 'skeleton' => 'cards3', 'after' => 'after_related'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function categoryDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Category (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'exams', 'label' => 'Exams', 'skeleton' => 'cards4', 'after' => 'between_sections'],
            ['id' => 'blogs', 'label' => 'Related blogs', 'skeleton' => 'cards3', 'after' => 'after_blogs'],
            ['id' => 'news', 'label' => 'Related news', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function authorDetailLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Author profile (H1)', 'skeleton' => 'author_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'exams', 'label' => 'Latest exams', 'skeleton' => 'cards3', 'after' => 'between_sections'],
            ['id' => 'blogs', 'label' => 'Latest blogs', 'skeleton' => 'cards3', 'after' => 'after_blogs'],
            ['id' => 'news', 'label' => 'Latest news', 'skeleton' => 'cards3', 'after' => 'after_news'],
            ['id' => 'questions', 'label' => 'Latest questions', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examAttemptLayout(): array
    {
        return [
            ['id' => 'topbar', 'label' => 'Exam top bar', 'skeleton' => 'exam_topbar', 'after' => 'above_title'],
            ['id' => 'question', 'label' => 'Question area', 'skeleton' => 'exam_question', 'after' => 'below_content'],
            ['id' => 'rail', 'label' => 'Exam panel (before Final submit)', 'skeleton' => 'exam_rail', 'after' => 'before_final_submit'],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examResultLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Exam result (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'summary', 'label' => 'Score summary', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'details', 'label' => 'Result details', 'skeleton' => 'section', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examRulesLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Exam rules & verification', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'stats', 'label' => 'Exam stats', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'warning', 'label' => 'Warnings callout', 'skeleton' => 'section', 'after' => 'after_about'],
            ['id' => 'summary', 'label' => 'Assessment summary', 'skeleton' => 'section', 'after' => 'after_details'],
            ['id' => 'instructions', 'label' => 'Instructions', 'skeleton' => 'prose', 'after' => 'between_sections'],
            ['id' => 'rules', 'label' => 'Exam rules', 'skeleton' => 'faq', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function examPrepareLayout(): array
    {
        return [
            ['id' => 'hero', 'label' => 'Exam readiness', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'checklist', 'label' => 'Verification checklist', 'skeleton' => 'faq', 'after' => 'between_sections'],
            ['id' => 'permissions', 'label' => 'Device permissions', 'skeleton' => 'section', 'after' => 'after_details'],
            ['id' => 'media', 'label' => 'Camera / mic checks', 'skeleton' => 'section', 'after' => 'after_content'],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function faqsLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'FAQs (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'groups', 'label' => 'FAQ groups', 'skeleton' => 'faq', 'after' => 'after_faqs'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * About Us — static marketing page sections.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function aboutLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'About hero (H1)', 'skeleton' => 'hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'stats', 'label' => 'Stats strip', 'skeleton' => 'stats', 'after' => 'after_stats'],
            ['id' => 'who', 'label' => 'Who we are', 'skeleton' => 'section', 'after' => 'after_about'],
            ['id' => 'mission', 'label' => 'Mission & vision', 'skeleton' => 'cards3', 'after' => 'after_mission'],
            ['id' => 'offers', 'label' => 'What we offer', 'skeleton' => 'cards4', 'after' => 'after_offers'],
            ['id' => 'why', 'label' => 'Why choose us', 'skeleton' => 'cards3', 'after' => 'after_why'],
            ['id' => 'timeline', 'label' => 'How we help', 'skeleton' => 'section', 'after' => 'after_timeline'],
            ['id' => 'values', 'label' => 'Values', 'skeleton' => 'chips', 'after' => 'after_values'],
            ['id' => 'faqs', 'label' => 'About FAQs', 'skeleton' => 'faq', 'after' => 'after_faqs'],
            ['id' => 'cta', 'label' => 'Call to action', 'skeleton' => 'cta', 'after' => 'after_cta'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Contact Us — details, form, map, support FAQ, CTA.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function contactLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Contact hero (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'details', 'label' => 'Contact details', 'skeleton' => 'cards4', 'after' => 'after_details'],
            ['id' => 'form', 'label' => 'Contact form', 'skeleton' => 'section', 'after' => 'after_form'],
            ['id' => 'map', 'label' => 'Office location', 'skeleton' => 'banner', 'after' => 'after_map'],
            ['id' => 'faqs', 'label' => 'Support FAQ', 'skeleton' => 'faq', 'after' => 'after_faqs'],
            ['id' => 'cta', 'label' => 'Call to action', 'skeleton' => 'cta', 'after' => 'after_cta'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Privacy / Terms legal pages — hero + accordion sections.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function legalLayout(string $title = 'Legal'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => $title.' (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'intro', 'label' => 'Policy intro', 'skeleton' => 'section', 'after' => 'before_content'],
            ['id' => 'sections', 'label' => 'Legal sections', 'skeleton' => 'faq', 'after' => 'after_legal_sections'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function searchLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => 'Search (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'results', 'label' => 'Search results', 'skeleton' => 'cards3', 'after' => 'after_results'],
            ['id' => 'more', 'label' => 'More results', 'skeleton' => 'cards3', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * Generic CMS / help page.
     *
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function simpleLayout(string $title = 'Page'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'hero', 'label' => $title.' (H1)', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'body', 'label' => 'Page content', 'skeleton' => 'prose', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function accountLayout(): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'toolbar', 'label' => 'Account toolbar', 'skeleton' => 'page_hero', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'content', 'label' => 'Account content', 'skeleton' => 'section', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @return list<array{id: string, label: string, skeleton: string, after: ?string, before?: ?string, chrome?: bool}>
     */
    public static function errorLayout(string $code = '404'): array
    {
        return [
            ['id' => 'header', 'label' => 'Navbar', 'skeleton' => 'header', 'after' => 'after_header', 'chrome' => true],
            ['id' => 'error', 'label' => 'Error '.$code, 'skeleton' => 'error', 'before' => 'above_title', 'after' => 'below_title'],
            ['id' => 'actions', 'label' => 'Actions', 'skeleton' => 'cta', 'after' => 'after_content'],
            ['id' => 'footer', 'label' => 'Footer', 'skeleton' => 'footer', 'before' => 'above_footer', 'chrome' => true],
        ];
    }

    /**
     * @param  list<array{id: string, label: string, after: string}>  $sidebarBlocks
     * @param  list<array{id: string, label: string, skeleton: string, after?: ?string, before?: ?string, chrome?: bool}>  $blocks
     * @param  list<string>  $sidebars
     * @return list<string>
     */
    public static function positionsFromBlocks(array $blocks, array $sidebars = [], array $sidebarBlocks = []): array
    {
        $keys = [];
        $hasAfterHeader = collect($blocks)->contains(
            fn (array $block): bool => ($block['after'] ?? null) === 'after_header'
        );

        foreach ($blocks as $block) {
            // The navbar and H1 are adjacent in these layouts. Keep one clear
            // insertion point after the navbar instead of showing two actions.
            if (! empty($block['before'])
                && ! ($hasAfterHeader && $block['before'] === 'above_title')) {
                $keys[] = $block['before'];
            }
            if (! empty($block['after'])) {
                $keys[] = $block['after'];
            }
        }

        foreach ($sidebarBlocks as $sidebarBlock) {
            if (! empty($sidebarBlock['after'])) {
                $keys[] = $sidebarBlock['after'];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Live page side-column content sections. Each section gets an ad insert below it.
     * after keys use left_after_* or right_after_* to match the real column side.
     *
     * @return list<array{id: string, label: string, after: string}>
     */
    public static function sidebarBlocks(string $pageKey): array
    {
        $right = static fn (string $id, string $label): array => [
            'id' => $id,
            'label' => $label,
            'after' => 'right_after_'.$id,
        ];
        $left = static fn (string $id, string $label): array => [
            'id' => $id,
            'label' => $label,
            'after' => 'left_after_'.$id,
        ];

        return match ($pageKey) {
            'exam_list', 'question_list', 'blog_list', 'news_list' => [
                $right('categories', 'Categories'),
            ],
            'exam_detail' => [
                $right('actions', 'Exam actions'),
                $right('facts', 'Quick facts'),
                $right('tags', 'Tags'),
                $right('monitoring', 'Monitoring notice'),
            ],
            'exam_rules' => [
                $right('next_step', 'Your next step'),
                $right('consent', 'Consent'),
                $right('start', 'Start / purchase'),
                $right('back', 'Back to exam details'),
            ],
            'exam_prepare' => [
                $right('summary', 'Ready to start?'),
                $right('consent', 'Consent'),
                $right('start', 'Start exam'),
            ],
            'exam_attempt' => [
                $right('overview', 'Exam details'),
                $right('webcam', 'Webcam'),
                $right('palette', 'Questions'),
                $right('final_submit', 'Final submit'),
            ],
            'question_detail' => [
                $right('overview', 'Question details'),
                $right('tags', 'Tags'),
                $right('related_questions', 'Related questions'),
                $right('categories', 'Categories'),
            ],
            'blog_detail' => [
                $right('author', 'Written by'),
                $right('tags', 'Tags'),
                $right('categories', 'Categories'),
                $right('latest', 'Latest blogs'),
            ],
            'news_detail' => [
                $right('details', 'Article details'),
                $right('author', 'Written by'),
                $right('tags', 'Tags'),
                $right('categories', 'Categories'),
                $right('latest', 'Latest news'),
            ],
            'category_detail' => [
                $right('subcategories', 'Subcategories'),
            ],
            'contact_us' => [
                $right('hours', 'Business hours'),
                $right('social', 'Social media'),
                $right('links', 'Quick links'),
            ],
            'faqs' => [
                $left('search', 'Search FAQs'),
                $left('categories', 'Categories'),
                $left('support', 'Contact support'),
            ],
            'privacy_policy' => [
                $left('toc', 'On this page'),
                $left('support', 'Contact support'),
            ],
            'terms' => [
                $left('toc', 'On this page'),
                $left('privacy', 'Privacy Policy'),
            ],
            'account' => [
                $left('nav', 'Account navigation'),
            ],
            default => [],
        };
    }

    /**
     * @param  list<array{id: string, label: string, skeleton: string, after?: ?string, before?: ?string, chrome?: bool}>  $blocks
     * @param  list<string>  $sidebars
     * @return array{
     *     label: string,
     *     group: string,
     *     layout: string,
     *     description: string,
     *     sidebars: list<string>,
     *     layout_blocks: list<array>,
     *     sidebar_blocks: list<array{id: string, label: string, after: string}>,
     *     positions: list<string>
     * }
     */
    public static function definePage(
        string $pageKey,
        string $label,
        string $group,
        string $layout,
        string $description,
        array $blocks,
        array $sidebars = []
    ): array {
        $allSidebarBlocks = self::sidebarBlocks($pageKey);
        $sidebarBlocks = array_values(array_filter(
            $allSidebarBlocks,
            function (array $block) use ($sidebars): bool {
                $side = str_starts_with((string) ($block['after'] ?? ''), 'left_') ? 'left' : 'right';

                return in_array($side, $sidebars, true);
            }
        ));

        return [
            'label' => $label,
            'group' => $group,
            'layout' => $layout,
            'description' => $description,
            'sidebars' => $sidebars,
            'layout_blocks' => $blocks,
            'sidebar_blocks' => $sidebarBlocks,
            'positions' => self::positionsFromBlocks($blocks, $sidebars, $sidebarBlocks),
        ];
    }

    /**
     * @return list<string>
     */
    public static function homePositionKeys(): array
    {
        return self::positionsFromBlocks(self::homeLayout(), [], []);
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     group: string,
     *     layout: string,
     *     description: string,
     *     sidebars: list<string>,
     *     layout_blocks: list<array>,
     *     positions: list<string>
     * }>
     */
    public static function pages(): array
    {
        // Side columns follow the live frontend: only pages with a real
        // left/right content rail get a matching ad column in the preview.
        $left = ['left'];
        $right = ['right'];
        $none = [];

        return [
            'home' => self::definePage(
                'home',
                'Home',
                'Core',
                'home',
                'Public homepage — stacked sections with no content sidebar.',
                self::homeLayout(),
                $none
            ),
            'exam_list' => self::definePage('exam_list', 'Exams — List', 'Exams', 'listing', 'Exam catalog with main results and one right Categories sidebar.', self::listingLayout('Exams'), $right),
            'exam_detail' => self::definePage('exam_detail', 'Exams — Detail', 'Exams', 'detail', 'Exam overview with a right actions/facts sidebar.', self::examDetailLayout(), $right),
            'exam_rules' => self::definePage('exam_rules', 'Exams — Rules', 'Exams', 'detail', 'Exam rules with a right start-exam sidebar.', self::examRulesLayout(), $right),
            'exam_prepare' => self::definePage('exam_prepare', 'Exams — Prepare', 'Exams', 'detail', 'Candidate readiness with a right summary sidebar.', self::examPrepareLayout(), $right),
            'exam_attempt' => self::definePage('exam_attempt', 'Exams — Live Attempt', 'Exams', 'exam_attempt', 'In-progress exam runner with the right exam panel.', self::examAttemptLayout(), $right),
            'exam_result' => self::definePage('exam_result', 'Exams — Result', 'Exams', 'detail', 'Exam result page with no content sidebar.', self::examResultLayout(), $none),
            'question_list' => self::definePage('question_list', 'Questions — List', 'Questions', 'listing', 'Question listing with main results and one right Categories sidebar.', self::listingLayout('Questions'), $right),
            'question_categories' => self::definePage('question_categories', 'Questions — Categories', 'Questions', 'listing', 'Question categories grid with no content sidebar.', self::listingLayout('Question categories'), $none),
            'question_detail' => self::definePage('question_detail', 'Questions — Detail', 'Questions', 'detail', 'Practice question with a right resources sidebar.', self::questionDetailLayout(), $right),
            'blog_list' => self::definePage('blog_list', 'Blogs — List', 'Blogs', 'listing', 'Blog listing with main results and one right Categories sidebar.', self::listingLayout('Blogs'), $right),
            'blog_detail' => self::definePage('blog_detail', 'Blogs — Detail', 'Blogs', 'article', 'Blog article with a right author/resources sidebar.', self::blogArticleLayout(), $right),
            'news_list' => self::definePage('news_list', 'News — List', 'News', 'listing', 'News listing with main results and one right Categories sidebar.', self::listingLayout('News'), $right),
            'news_detail' => self::definePage('news_detail', 'News — Detail', 'News', 'article', 'News article with a right article resources sidebar.', self::newsArticleLayout(), $right),
            'categories' => self::definePage('categories', 'Categories', 'Browse', 'listing', 'Category browsing with no content sidebar.', self::listingLayout('Categories'), $none),
            'category_detail' => self::definePage('category_detail', 'Category — Detail', 'Browse', 'detail', 'Category content with a right Subcategories sidebar.', self::categoryDetailLayout(), $right),
            'authors' => self::definePage('authors', 'Authors — List', 'Browse', 'listing', 'Author listing with no content sidebar.', self::listingLayout('Authors'), $none),
            'author_detail' => self::definePage('author_detail', 'Author — Detail', 'Browse', 'detail', 'Author profile with no content sidebar.', self::authorDetailLayout(), $none),
            'faqs' => self::definePage('faqs', 'FAQs', 'Pages', 'simple', 'FAQ groups with a left tools sidebar.', self::faqsLayout(), $left),
            'about_us' => self::definePage('about_us', 'About Us', 'Pages', 'simple', 'About sections with no content sidebar.', self::aboutLayout(), $none),
            'contact_us' => self::definePage('contact_us', 'Contact Us', 'Pages', 'simple', 'Contact form with a right support sidebar.', self::contactLayout(), $right),
            'privacy_policy' => self::definePage('privacy_policy', 'Privacy Policy', 'Pages', 'simple', 'Privacy sections with a left on-this-page sidebar.', self::legalLayout('Privacy Policy'), $left),
            'terms' => self::definePage('terms', 'Terms & Conditions', 'Pages', 'simple', 'Terms sections with a left on-this-page sidebar.', self::legalLayout('Terms & Conditions'), $left),
            'help_center' => self::definePage('help_center', 'Help Center', 'Pages', 'simple', 'Help content with no dedicated content sidebar.', self::simpleLayout('Help Center'), $none),
            'search' => self::definePage('search', 'Search', 'Pages', 'listing', 'Search results with no content sidebar.', self::searchLayout(), $none),
            'sitemap' => self::definePage('sitemap', 'Sitemap', 'Pages', 'simple', 'Sitemap content with no content sidebar.', self::simpleLayout('Sitemap'), $none),
            'cms_page' => self::definePage('cms_page', 'CMS Pages (other)', 'Pages', 'simple', 'Default CMS content with no content sidebar.', self::simpleLayout('CMS page'), $none),
            'account' => self::definePage('account', 'Account Area', 'Account', 'simple', 'Candidate account area with a left navigation sidebar.', self::accountLayout(), $left),
            'error_404' => self::definePage('error_404', 'Error 404', 'Errors', 'error', 'Page not found.', self::errorLayout('404'), $none),
            'error_403' => self::definePage('error_403', 'Error 403', 'Errors', 'error', 'Forbidden / access denied.', self::errorLayout('403'), $none),
            'error_419' => self::definePage('error_419', 'Error 419', 'Errors', 'error', 'Page expired / CSRF token mismatch.', self::errorLayout('419'), $none),
            'error_429' => self::definePage('error_429', 'Error 429', 'Errors', 'error', 'Too many requests.', self::errorLayout('429'), $none),
            'error_500' => self::definePage('error_500', 'Error 500', 'Errors', 'error', 'Server error.', self::errorLayout('500'), $none),
            'error_503' => self::definePage('error_503', 'Error 503', 'Errors', 'error', 'Service unavailable / maintenance.', self::errorLayout('503'), $none),
        ];
    }

    /**
     * @return array<string, array{label: string, note: string, multi: bool}>
     */
    public static function positions(): array
    {
        $multi = array_flip(self::MULTI_POSITIONS);

        $defs = [
            'above_title' => ['Before H1 / page title', 'Shows directly before the main H1 / page heading.'],
            'below_title' => ['Below page title', 'Shows under the heading, before primary content.'],
            'before_h2' => ['Before each H2', 'Injected before every H2 in blog/news article bodies. Multiple ads allowed and rotated.'],
            'before_content' => ['Before content', 'Inserted before the main content block.'],
            'after_content' => ['After content', 'Inserted after the main content block.'],
            'between_sections' => ['Between sections', 'Between major content sections. Multiple ads allowed.'],
            'below_items' => ['Below every list item', 'After each card/row on listing pages. Multiple ads allowed.'],
            'below_content' => ['Below content', 'Under the primary interactive content area.'],
            'before_final_submit' => ['Before final submit', 'Exam panel slot between the question palette and Final submit.'],
            'left_sidebar' => ['Left-side ads', 'Legacy/ad-only left column. Prefer left_after_* slots that follow real sidebar sections.'],
            'right_top' => ['Right sidebar — top', 'Above the first right-sidebar section. Multiple ads allowed.'],
            'right_sidebar' => ['Right-side ads', 'Legacy/ad-only right column. Prefer right_after_* slots that follow real sidebar sections.'],
            'above_footer' => ['Above footer', 'Full-width strip just above the site footer.'],
            'after_header' => ['After navbar', 'Between the site navbar and the first content section.'],
            'after_hero' => ['After hero', 'Directly below the homepage hero carousel.'],
            'after_stats' => ['After stats', 'Below the stats / social-proof section.'],
            'after_featured_exams' => ['After featured exams', 'Below the featured exams grid.'],
            'after_questions' => ['After questions', 'Below the questions section.'],
            'after_categories' => ['After categories', 'Below the categories section.'],
            'after_blogs' => ['After blogs', 'Below the blogs section.'],
            'after_news' => ['After news', 'Below the news section.'],
            'after_testimonials' => ['After testimonials', 'Below the testimonials section.'],
            'after_faqs' => ['After FAQs', 'Below the FAQs section.'],
            'after_newsletter' => ['After newsletter', 'Below the newsletter band.'],
            'after_cta' => ['After CTA', 'Below a call-to-action section.'],
            'after_filters' => ['After filters', 'Below the listing filter toolbar.'],
            'after_about' => ['After about', 'Below the about / who-we-are section.'],
            'after_details' => ['After details', 'Below details / meta cards.'],
            'after_related' => ['After related', 'Below related content blocks.'],
            'after_results' => ['After results', 'Below the first search results group.'],
            'after_share' => ['After share', 'Below the share panel on detail pages.'],
            'after_attempts' => ['After attempts', 'Below previous attempts on exam detail.'],
            'after_feedback' => ['After feedback', 'Below the feedback block on exam detail.'],
            'after_form' => ['After form', 'Below the contact form section.'],
            'after_map' => ['After map', 'Below the office location / map section.'],
            'after_mission' => ['After mission', 'Below mission & vision on About Us.'],
            'after_offers' => ['After offers', 'Below the what-we-offer grid on About Us.'],
            'after_why' => ['After why', 'Below the why-choose-us section on About Us.'],
            'after_timeline' => ['After timeline', 'Below the how-we-help timeline on About Us.'],
            'after_values' => ['After values', 'Below the values section on About Us.'],
            'after_legal_sections' => ['After legal sections', 'Below privacy/terms accordion sections.'],
            'after_explanation' => ['After explanation', 'Below the question explanation panel.'],
            'after_options' => ['After options', 'Below the question options panel.'],
        ];

        $out = [];
        foreach ($defs as $key => [$label, $note]) {
            $out[$key] = [
                'label' => $label,
                'note' => $note,
                'multi' => isset($multi[$key]) || self::isRightSidebarSlot($key),
            ];
        }

        foreach (self::pages() as $page) {
            foreach ($page['sidebar_blocks'] ?? [] as $block) {
                $after = $block['after'] ?? null;
                if (! is_string($after) || $after === '' || isset($out[$after])) {
                    continue;
                }

                $out[$after] = [
                    'label' => 'After '.$block['label'].' (sidebar)',
                    'note' => 'Inserted below the “'.$block['label'].'” sidebar section. Multiple ads allowed.',
                    'multi' => true,
                ];
            }
        }

        return $out;
    }

    public static function defaultPageKey(): string
    {
        return 'home';
    }

    public static function page(string $key): ?array
    {
        return self::pages()[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function pageKeys(): array
    {
        return array_keys(self::pages());
    }

    /**
     * @return list<string>
     */
    public static function positionKeysForPage(string $pageKey): array
    {
        return self::page($pageKey)['positions'] ?? [];
    }

    public static function allowsMultiple(string $positionKey): bool
    {
        return in_array($positionKey, self::MULTI_POSITIONS, true)
            || self::isSidePlacementSlot($positionKey);
    }

    public static function isRightSidebarSlot(string $positionKey): bool
    {
        return $positionKey === 'right_top'
            || $positionKey === 'right_sidebar'
            || str_starts_with($positionKey, 'right_after_');
    }

    public static function isLeftSidebarSlot(string $positionKey): bool
    {
        return $positionKey === 'left_sidebar'
            || str_starts_with($positionKey, 'left_after_');
    }

    public static function isSidePlacementSlot(string $positionKey): bool
    {
        return self::isLeftSidebarSlot($positionKey)
            || self::isRightSidebarSlot($positionKey);
    }

    /**
     * @return list<string>
     */
    public static function typeKeys(): array
    {
        return array_keys(self::types());
    }

    /**
     * @return list<string>
     */
    public static function bannerSizeKeys(): array
    {
        return array_keys(self::bannerSizes());
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function pagesGrouped(): array
    {
        $grouped = [];
        foreach (self::pages() as $key => $page) {
            $grouped[$page['group']][$key] = $page['label'];
        }

        return $grouped;
    }
}
