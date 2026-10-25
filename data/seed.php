<?php
/**
 * 模拟数据：24 个用户、6 个分类、24 个活动、若干报名记录
 */

function seed_database(PDO $pdo): void
{
    $categories = [
        ['sports', 'Sports', 'Games, tournaments and outdoor adventures', '#E4572E', 'trophy', 1],
        ['arts', 'Arts & Culture', 'Music, theatre, film and gallery nights', '#7B4BC4', 'palette', 2],
        ['academic', 'Academic & Career', 'Talks, workshops and career prep', '#1F6FEB', 'book', 3],
        ['service', 'Service & Impact', 'Volunteering and community outreach', '#1E9E6A', 'hand', 4],
        ['social', 'Social & Community', 'Food, friends and campus traditions', '#F2A900', 'users', 5],
        ['wellness', 'Health & Wellness', 'Fitness, mindfulness and self-care', '#0FA3B1', 'leaf', 6],
    ];

    $catStmt = $pdo->prepare(
        'INSERT INTO categories (slug, name, description, color, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($categories as $c) {
        $catStmt->execute($c);
    }
    $catIds = [];
    foreach ($pdo->query('SELECT id, slug FROM categories') as $row) {
        $catIds[$row['slug']] = (int) $row['id'];
    }

    $users = [
        ['Emma Carter', 'emma.carter@taylor.edu', 'S-104287', 'Journalism & Media', 'Junior', 'Student government reporter. Always chasing a good story and free pizza.', 'Writing, Podcasting, Volleyball'],
        ['Noah Bennett', 'noah.bennett@taylor.edu', 'S-104512', 'Computer Science', 'Sophomore', 'Building apps by day, playing intramural soccer by night.', 'Coding, Soccer, Coffee'],
        ['Olivia Reed', 'olivia.reed@taylor.edu', 'S-103884', 'Nursing', 'Senior', 'Volunteer at the local clinic. Coffee enthusiast.', 'Volunteering, Running, Baking'],
        ['Liam Foster', 'liam.foster@taylor.edu', 'S-105003', 'Business Analytics', 'Junior', 'Running the campus investment club. Ask me about spreadsheets.', 'Finance, Tennis, Podcasts'],
        ['Sophia Nguyen', 'sophia.nguyen@taylor.edu', 'S-104119', 'Graphic Design', 'Sophomore', 'Poster designer for half the events on this site.', 'Design, Illustration, Film'],
        ['Ethan Brooks', 'ethan.brooks@taylor.edu', 'S-103650', 'Biology', 'Senior', 'Pre-med, plant dad, and worship team drummer.', 'Biology, Plants, Drumming'],
        ['Ava Thompson', 'ava.thompson@taylor.edu', 'S-104876', 'Psychology', 'Freshman', 'New to campus and saying yes to everything.', 'Reading, Yoga, Board Games'],
        ['Mason Turner', 'mason.turner@taylor.edu', 'S-104302', 'Mechanical Engineering', 'Junior', 'Robotics team lead. Will explain gears unprompted.', 'Robotics, Cycling, Hiking'],
        ['Isabella Hayes', 'isabella.hayes@taylor.edu', 'S-103977', 'Elementary Education', 'Senior', 'Future teacher, current chapel band singer.', 'Teaching, Music, Crafts'],
        ['Lucas Warren', 'lucas.warren@taylor.edu', 'S-105144', 'History', 'Sophomore', 'Museum nerd and campus tour guide.', 'History, Photography, Chess'],
        ['Mia Delgado', 'mia.delgado@taylor.edu', 'S-104533', 'Social Work', 'Junior', 'Community organizer with a soft spot for service days.', 'Service, Cooking, Dance'],
        ['James Whitfield', 'james.whitfield@taylor.edu', 'S-103211', 'Chemistry', 'Senior', 'Lab rat and coffee brewer.', 'Chemistry, Brewing, Basketball'],
        ['Charlotte King', 'charlotte.king@taylor.edu', 'S-104760', 'Marketing', 'Sophomore', 'Social media coordinator for student activities.', 'Marketing, Photography, Travel'],
        ['Benjamin Ruiz', 'benjamin.ruiz@taylor.edu', 'S-104028', 'Theatre Arts', 'Junior', 'Stage manager, occasional actor, full-time dramatic.', 'Theatre, Improv, Film'],
        ['Amelia Stone', 'amelia.stone@taylor.edu', 'S-103549', 'Mathematics', 'Senior', 'Tutor at the math center. Puzzle addict.', 'Math, Puzzles, Climbing'],
        ['Henry Caldwell', 'henry.caldwell@taylor.edu', 'S-104912', 'Biblical Studies', 'Sophomore', 'Small group leader and ultimate frisbee captain.', 'Theology, Frisbee, Hiking'],
        ['Harper Lane', 'harper.lane@taylor.edu', 'S-104387', 'Public Health', 'Junior', 'Wellness advocate and smoothie scientist.', 'Wellness, Nutrition, Yoga'],
        ['Alexander Pope', 'alexander.pope@taylor.edu', 'S-103705', 'Physics', 'Senior', 'Stargazing club president.', 'Astronomy, Physics, Chess'],
        ['Evelyn Marsh', 'evelyn.marsh@taylor.edu', 'S-104651', 'English Literature', 'Freshman', 'Poetry open mic regular.', 'Poetry, Books, Tea'],
        ['Daniel Okafor', 'daniel.okafor@taylor.edu', 'S-105288', 'Computer Engineering', 'Junior', 'Hackathon veteran. Sleeps occasionally.', 'Coding, Hackathons, Gaming'],
        ['Grace Sullivan', 'grace.sullivan@taylor.edu', 'S-103468', 'Music Education', 'Senior', 'Choir section leader and piano teacher.', 'Music, Piano, Choir'],
        ['Sebastian Cole', 'sebastian.cole@taylor.edu', 'S-104722', 'Economics', 'Sophomore', 'Debate club and pickup basketball.', 'Economics, Debate, Basketball'],
        ['Chloe Barrett', 'chloe.barrett@taylor.edu', 'S-104015', 'Art History', 'Junior', 'Gallery volunteer with strong opinions on color.', 'Art, Museums, Ceramics'],
        ['Ryan Mitchell', 'ryan.mitchell@taylor.edu', 'S-103822', 'Kinesiology', 'Senior', 'Strength coach in training. Ask about protein.', 'Fitness, Weightlifting, Running'],
    ];

    $hash = password_hash('password123', PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare(
        'INSERT INTO users (name, email, student_id, major, year, bio, interests, password_hash, role, verified, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
    );
    $now = date('Y-m-d H:i:s');
    foreach ($users as $i => $u) {
        $userStmt->execute([
            $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $u[6], $hash,
            $i < 12 ? 'organizer' : 'student',
            date('Y-m-d H:i:s', strtotime('-' . (60 + $i * 3) . ' days')),
        ]);
    }
    $userIds = [];
    foreach ($pdo->query('SELECT id, email FROM users') as $row) {
        $userIds[$row['email']] = (int) $row['id'];
    }

    $today = new DateTimeImmutable('today', new DateTimeZone(CAMPUS_TZ));
    $at = function (int $offset, string $time, int $minutes) use ($today): array {
        [$h, $m] = array_map('intval', explode(':', $time));
        $start = $today->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->setTime($h, $m);
        return [$start->format('Y-m-d H:i:s'), $start->modify('+' . $minutes . ' minutes')->format('Y-m-d H:i:s')];
    };

    // [标题, 分类, 主办人邮箱, 地点, 天偏移, 开始时间, 时长分钟, 容量, 描述]
    $events = [
        ['Midnight Pancake Run', 'social', 'emma.carter@taylor.edu', 'Hodson Dining Commons', 0, '21:30', 120, 120,
            "Finals week is almost here, so we are flipping pancakes until midnight.\n\nCome for the maple syrup, stay for the awkward conversations with people you only see at 11pm. Vegan and gluten-free options available."],
        ['Intramural Soccer Finals', 'sports', 'noah.bennett@taylor.edu', 'Turner Stadium', 1, '16:00', 120, 200,
            "The intramural season comes down to one match. Bring a blanket, bring a friend, bring your loudest voice.\n\nFree hot chocolate for the first 100 fans."],
        ['Career Studio: Resume Refresh', 'academic', 'liam.foster@taylor.edu', 'Euler Science Complex 109', 2, '18:30', 90, 40,
            "Bring your resume and leave with a version that actually gets past the robots.\n\nCareer coaches will run 15-minute reviews, so come early to grab a slot."],
        ['Community Garden Workday', 'service', 'mia.delgado@taylor.edu', 'Upland Community Garden', 2, '09:00', 180, 30,
            "We are putting the beds to rest for winter. No experience needed - tools, gloves and snacks provided.\n\nMeet at the garden shed behind the physical plant."],
        ['Open Mic Night at The Well', 'arts', 'benjamin.ruiz@taylor.edu', 'The Well Coffeehouse', 3, '19:30', 150, 60,
            "Poetry, acoustic sets, stand-up, and whatever else you have been rehearsing in the shower.\n\nSign-up sheet opens at 7pm. Five minutes per performer."],
        ['Sunrise Yoga on the Mall', 'wellness', 'harper.lane@taylor.edu', 'Taylor Mall (East Lawn)', 4, '06:45', 60, 35,
            "Start your Thursday with slow flow yoga as the sun comes up over the bell tower.\n\nBring a mat and a layer. All levels welcome."],
        ['Tech Talk: Building with AI', 'academic', 'daniel.okafor@taylor.edu', 'Nussbaum Science Center 201', 4, '17:00', 90, 80,
            "Three student teams demo what they built in a weekend, followed by an open Q&A about tools, ethics and jobs.\n\nPizza after the talks."],
        ['Fall Theatre: The Tempest', 'arts', 'isabella.hayes@taylor.edu', 'Mitchell Theatre', 5, '19:00', 150, 150,
            "Shakespeare's shipwreck comedy, staged with live music and a set built entirely by students.\n\nDoors open 30 minutes before curtain."],
        ['Campus Cleanup Challenge', 'service', 'olivia.reed@taylor.edu', 'Kesler Student Center', 6, '10:00', 120, 50,
            "Team up, clean up, win prizes. We are splitting into four teams and covering the whole campus.\n\nGloves and bags provided. Winning team gets a dining hall gift card."],
        ['Basketball Shootout', 'sports', 'james.whitfield@taylor.edu', 'Odle Arena', 6, '19:00', 120, 100,
            "Three-point contest, knockout tournament, and a faculty vs students exhibition game.\n\nRegister a team of two or come solo and we will pair you up."],
        ['International Food Night', 'social', 'grace.sullivan@taylor.edu', 'Hodson Dining Commons', 7, '18:00', 150, 180,
            "Twenty countries, twenty tables, one very full dining hall. Student cooks are making family recipes.\n\nBring your appetite and a friend who has never been."],
        ['Meditation & Mindfulness Hour', 'wellness', 'amelia.stone@taylor.edu', 'Rediger Chapel Prayer Room', 8, '12:15', 60, 25,
            "A guided breathing and meditation session in the middle of your day.\n\nCushions provided. Phones stay in your bag."],
        ['Startup Pitch Night', 'academic', 'sebastian.cole@taylor.edu', 'Euler Science Complex Auditorium', 9, '18:00', 150, 120,
            "Six student startups. Four minutes each. One panel of alumni judges.\n\nStay after to talk to the teams and steal their ideas."],
        ['Fall Festival & Bonfire', 'social', 'charlotte.king@taylor.edu', 'Campus Mall (West Lawn)', 10, '17:30', 240, 300,
            "Hayrides, cider, apple bobbing and a bonfire big enough to see from the library.\n\nBring a jacket. Bring a friend. Leave your homework at home."],
        ['Volleyball Open Gym', 'sports', 'ryan.mitchell@taylor.edu', 'Odle Arena Courts 2-3', 11, '20:00', 120, 40,
            "Open courts for pickup games. Teams rotate every match.\n\nKnee pads recommended, enthusiasm required."],
        ['Watercolor Workshop', 'arts', 'chloe.barrett@taylor.edu', 'Metcalf Art Studio', 12, '15:00', 120, 20,
            "Learn washes, layering and how to stop overworking your painting. All supplies included.\n\nSpace is limited to 20 easels."],
        ['Career Fair Prep Session', 'academic', 'liam.foster@taylor.edu', 'Kesler Student Center Room 201', 13, '17:30', 60, 60,
            "What to wear, what to bring, what to say, and how to follow up without being annoying.\n\nBring a notebook and one specific question."],
        ['Sunday Community Lunch', 'service', 'mia.delgado@taylor.edu', 'Upland Community Center', 14, '12:00', 120, 45,
            "We are cooking and serving lunch for neighbors in Upland. Volunteers arrive at 11:15 for prep.\n\nDrivers needed - sign up and we will match you with a car."],
        ['Praise & Worship Night', 'arts', 'grace.sullivan@taylor.edu', 'Rediger Chapel', 15, '20:00', 90, 250,
            "An evening of student-led worship with the chapel band. Doors at 7:30.\n\nAll students, staff and faculty welcome."],
        ['Hiking Trip: Turkey Run', 'wellness', 'henry.caldwell@taylor.edu', 'Turkey Run State Park', 16, '08:00', 480, 24,
            "Van leaves from the Kesler lot at 8am sharp. Expect 7 miles of trails, a creek crossing and a lot of snacks.\n\nBring hiking shoes, water and $8 for the van."],
        ['Film Screening: Student Shorts', 'arts', 'sophia.nguyen@taylor.edu', 'Mitchell Theatre', 17, '19:00', 120, 140,
            "Twelve short films made by students this semester, followed by a Q&A with the directors.\n\nPopcorn provided, opinions optional."],
        ['Hackathon Kickoff', 'academic', 'daniel.okafor@taylor.edu', 'Nussbaum Science Center 100', 18, '16:00', 300, 90,
            "Twenty-four hours, teams of four, one theme announced on stage.\n\nDinner is on us. Bring a laptop and a questionable amount of caffeine."],
        ['Sunrise Prayer Walk', 'service', 'evelyn.marsh@taylor.edu', 'Bell Tower', 19, '07:00', 60, 30,
            "A quiet walk across campus with stops to pray for each residence hall and academic building.\n\nWe finish with coffee at The Well."],
        ['Pickup Basketball at Odle', 'sports', 'sebastian.cole@taylor.edu', 'Odle Arena', 20, '18:00', 120, 30,
            "Full court, first to 11, winners stay on. All skill levels, no trash talk (okay, some trash talk).\n\nBring a light and dark shirt."],
        ['Christmas Cookie Decorating', 'social', 'emma.carter@taylor.edu', 'Kesler Student Center Lobby', -6, '18:00', 120, 60,
            "A campus tradition: decorate cookies, judge cookies, eat cookies.\n\nThis event has passed - thanks to everyone who came out."],
        ['Fall 5K Fun Run', 'wellness', 'ryan.mitchell@taylor.edu', 'Campus Mall (Start Line)', -13, '08:30', 120, 150,
            "A chilly but cheerful 5K around campus and Upland. Results posted in the student center.\n\nThis event has passed."],
    ];

    $eventStmt = $pdo->prepare(
        'INSERT INTO events (title, category_id, description, location, start_at, end_at, capacity, cover, organizer_id, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($events as $index => $ev) {
        [$startAt, $endAt] = $at($ev[4], $ev[5], $ev[6]);
        $eventStmt->execute([
            $ev[0],
            $catIds[$ev[1]],
            $ev[8],
            $ev[3],
            $startAt,
            $endAt,
            $ev[7],
            null,
            $userIds[$ev[2]],
            'published',
            date('Y-m-d H:i:s', strtotime('-' . (30 - $index) . ' days')),
        ]);
    }

    // 报名记录：为每个活动随机分配 4-24 名用户，不超过容量
    $eventRows = $pdo->query('SELECT id, capacity, organizer_id FROM events ORDER BY id')->fetchAll();
    $regStmt = $pdo->prepare(
        'INSERT OR IGNORE INTO registrations (event_id, user_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)'
    );
    mt_srand(20261025);
    foreach ($eventRows as $row) {
        $target = min((int) $row['capacity'] - 2, mt_rand(6, 22));
        $pool = array_values($userIds);
        shuffle($pool);
        $picked = array_slice($pool, 0, max(2, $target));
        foreach ($picked as $uid) {
            $created = date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 20) . ' days'));
            $regStmt->execute([(int) $row['id'], $uid, 'going', $created, $created]);
        }
    }
}
