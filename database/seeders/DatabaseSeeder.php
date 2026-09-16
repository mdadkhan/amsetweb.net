<?php

namespace Database\Seeders;

use App\Models\Conference;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\GalleryItem;
use App\Models\Initiative;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Page;
use App\Models\Person;
use App\Models\Post;
use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => env('AMSET_ADMIN_EMAIL', 'admin@amsetweb.test'),
        ], [
            'name' => 'AMSET Administrator',
            'password' => Hash::make(env('AMSET_ADMIN_PASSWORD', 'ChangeMe!123')),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $initiatives = [
            ['AMSET AI Advocacy', 'ai-advocacy', 'Responsible innovation', 'Advancing informed, ethical public discussion about artificial intelligence.', '#ai-advocacy'],
            ['Science Fair', 'science-fair', 'Young minds at work', 'Connecting students with scientists through inquiry, experimentation, and mentorship.', '#science-fair'],
            ['AMSET Webinar', 'webinar', 'Knowledge without borders', 'Accessible conversations with researchers, engineers, and technology professionals.', '#webinar'],
            ['Contribute to AMSET', 'contribute', 'Fund the next idea', 'Support science education, professional programs, and community initiatives.', '#contribute'],
        ];

        foreach ($initiatives as $index => [$name, $slug, $eyebrow, $description, $link]) {
            Initiative::query()->updateOrCreate(['slug' => $slug], compact('name', 'eyebrow', 'description', 'link') + [
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        foreach ([
            ['about-us', 'About AMSET', 'AMSET connects Muslim scientists, engineers, and technology professionals through service, education, and collaboration.', null],
            ['from-the-past', 'AMSE to AMSET: History at a Glance', 'Explore the milestones behind more than six decades of scientific community building.', null],
            ['membership', 'Membership', 'Join AMSET or renew your membership to participate in programs and strengthen the professional network.', null],
            ['future-holds', 'What the Future Holds', 'Growing AMSET\'s youth and innovation programs, including the Science Fair and hackathon events, to attract the next generation of Muslim scientists and engineers.', '<ul><li><strong>Science Fair</strong> &mdash; AMSET has sponsored and judged student science fairs, mentoring young researchers and connecting them with working scientists and engineers.</li><li><strong>Hackathons</strong> &mdash; AMSET has partnered with and participated in hackathon events, encouraging students and professionals to build technology solutions to real-world problems.</li><li>Additional program details are being documented and will be added here as they are finalized.</li></ul>'],
        ] as [$slug, $title, $summary, $body]) {
            Page::query()->updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'summary' => $summary,
                'body' => $body ?? '<p>'.$summary.'</p>',
                'is_published' => true,
            ]);
        }

        foreach ([
            ['Salman Hameed', 'salman-hameed', 'Eminent Scientist'],
            ['Abouheif', 'abouheif', 'Eminent Scientist'],
            ['Shanavas M.D.', 'shanavas-m-d', 'Eminent Scientist'],
            ['Sample Executive Member', 'sample-executive-member', 'Executive Team'],
        ] as $index => [$name, $slug, $role]) {
            Person::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'role' => $role,
                'type' => $role === 'Executive Team' ? 'team' : 'scientist',
                'biography' => $role === 'Executive Team' ? 'Sample record for local development; replace in the admin portal.' : 'Legacy AMSET scientist profile imported from the public site index.',
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        foreach ([
            ['1963', 'The AMSE legacy begins', 'A professional community forms around science, engineering, and service.'],
            ['2014', 'A renewed digital presence', 'AMSET expands online access to its history, conference programs, and professional network.'],
            ['2026', 'Building the next chapter', 'New advocacy, webinar, science fair, and membership programs are brought together.'],
        ] as $index => [$year, $title, $description]) {
            TimelineEvent::query()->updateOrCreate(['year' => $year, 'title' => $title], [
                'description' => $description,
                'sort_order' => $index + 1,
                'is_published' => true,
            ]);
        }

        foreach ([2025, 2024, 2023] as $year) {
            Conference::query()->updateOrCreate(['slug' => $year.'-annual-conference'], [
                'title' => $year.' Annual Conference',
                'summary' => 'Program archive for the '.$year.' AMSET Annual Conference.',
                'is_published' => true,
            ]);
        }

        $gallery = Gallery::query()->updateOrCreate(['slug' => 'annual-conference-highlights'], [
            'title' => 'Annual Conference Highlights',
            'description' => 'Sample event album ready for images and captions in the admin portal.',
            'is_published' => true,
        ]);

        GalleryItem::query()->updateOrCreate(['gallery_id' => $gallery->id, 'sort_order' => 1], [
            'image' => 'images/amset-logo.png',
            'caption' => 'AMSET event album placeholder',
        ]);

        Post::query()->updateOrCreate(['slug' => 'welcome-to-the-new-amset-website'], [
            'title' => 'Welcome to the new AMSET website',
            'excerpt' => 'A refreshed home for AMSET news, programs, conferences, and community resources.',
            'body' => '<p>This sample news record can be edited or replaced from the admin portal.</p>',
            'category' => 'news',
            'published_at' => now(),
        ]);

        $annualPlan = MembershipPlan::query()->updateOrCreate(['slug' => 'individual-annual'], [
            'name' => 'Individual Annual',
            'description' => 'Standard annual membership for individuals.',
            'price' => config('payments.membership_fees.individual-annual'),
            'billing_interval' => 'yearly',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MembershipPlan::query()->updateOrCreate(['slug' => 'student-annual'], [
            'name' => 'Student Annual',
            'description' => 'Discounted annual membership for full-time students.',
            'price' => config('payments.membership_fees.student-annual'),
            'billing_interval' => 'yearly',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Member::query()->updateOrCreate(['email' => 'sample.member@amsetweb.test'], [
            'membership_plan_id' => $annualPlan->id,
            'membership_number' => 'AMSET-2026-SAMPLE',
            'first_name' => 'Sample',
            'last_name' => 'Member',
            'status' => 'active',
            'joined_at' => now()->subYear(),
            'expires_at' => now()->addMonths(2),
        ]);

        $event = Event::query()->updateOrCreate(['slug' => 'amset-annual-science-fair'], [
            'title' => 'AMSET Annual Science Fair',
            'summary' => 'Student research showcase judged by AMSET scientists and engineers.',
            'location' => 'Virtual',
            'starts_at' => now()->addMonths(3),
            'is_published' => true,
        ]);

        $event->tickets()->updateOrCreate(['name' => 'General Admission'], [
            'price' => 0,
            'sort_order' => 1,
        ]);

        DonationCampaign::query()->updateOrCreate(['slug' => 'youth-innovation-fund'], [
            'title' => 'Youth Innovation Fund',
            'description' => 'Supports Science Fair mentorship and hackathon participation for students.',
            'goal_amount' => 10000,
            'is_active' => true,
        ]);
    }
}
