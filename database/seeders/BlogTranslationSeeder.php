<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BlogTranslationSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            'CyberZone відкриває зону для командних тренувань' => [
                'title' => 'CyberZone opens a team training area',
                'description' => 'The club has set up a dedicated area for teams preparing for competitions and practising together.',
                'text' => 'A new team training area with adjacent gaming PCs is now available at CyberZone. Players can practise tactics together, discuss mistakes after matches and prepare for club tournaments. The administrator can help arrange neighbouring stations and check voice chat before the session. For team training, we recommend agreeing on the number of participants and the length of the session in advance.',
            ],
            'У CyberZone запроваджено попереднє бронювання ПК' => [
                'title' => 'Advance PC bookings are now available at CyberZone',
                'description' => 'Visitors can arrange gaming stations in advance with the club administrator.',
                'text' => 'To plan a visit to CyberZone, you can now book a PC in advance through the administrator. When making a booking, specify your preferred time, the number of players and the approximate session length. The administrator will check station availability and confirm the booking. If your plans change, please notify the club in advance so that other visitors can use the available computers.',
            ],
            'Оновлення ігрових ПК: більше потужності для сучасних ігор' => [
                'title' => 'Gaming PC upgrades: more power for modern games',
                'description' => 'CyberZone is upgrading some gaming stations and testing them before returning them to the gaming floor.',
                'text' => 'The club is upgrading its gaming PCs in stages, with more powerful graphics cards and additional RAM planned for some stations. After installing the components, the team checks temperatures, system stability and performance in popular games. Particular attention is paid to graphics settings for a comfortable gaming experience. Ask the administrator which upgraded stations are available for your session.',
            ],
            'Нові монітори для змагальних матчів' => [
                'title' => 'New monitors for competitive matches',
                'description' => 'High refresh rate monitors have been installed at selected stations for fast-paced games.',
                'text' => 'CyberZone is upgrading the monitors at stations used for competitive matches. Before putting them into service, the staff check the connections, resolution and refresh rate selected in the system settings. Players can adjust the screen height and brightness to suit their needs. If the image flickers or you have questions about the settings, contact the administrator before starting a match.',
            ],
            'Профілактика обладнання та оновлення ігрової бібліотеки' => [
                'title' => 'Hardware maintenance and game library updates',
                'description' => 'The club team is servicing PCs and preparing game clients for upcoming sessions.',
                'text' => 'During scheduled maintenance, CyberZone technicians remove dust from PC cases and check the cooling systems and peripherals. Game client updates are installed at the same time to reduce waiting before play. Some PCs may be temporarily unavailable during these checks. The administrator can help you choose another station and let you know when the serviced PC is ready to use again.',
            ],
            'Турнір із Counter-Strike 2 у CyberZone' => [
                'title' => 'Counter-Strike 2 tournament at CyberZone',
                'description' => 'Teams are invited to a club Counter-Strike 2 tournament with advance registration.',
                'text' => 'CyberZone is preparing a club Counter-Strike 2 tournament for the local gaming community. Captains need to provide the administrator with the team name, player roster and contact details. Once registration closes, the organisers will agree on the schedule and publish the match format and participation rules. Before the start, each team will have time to check its settings, peripherals and voice chat.',
            ],
            'Командний вечір Dota 2 для спільноти клубу' => [
                'title' => 'Dota 2 team night for the club community',
                'description' => 'Dota 2 players are invited to friendly matches, opportunities to meet new teammates and discussions about team play.',
                'text' => 'CyberZone will host a Dota 2 team night featuring friendly matches between visitors. Both established teams and individual players are welcome; the organisers will help individual players find a team. Experience and preferred roles will be taken into account when assigning players. After the matches, participants can discuss key moments and arrange future training sessions together.',
            ],
            'Відкриті тренування для початківців у кіберспорті' => [
                'title' => 'Open training sessions for esports beginners',
                'description' => 'CyberZone invites newcomers to learn the basics of team communication and match preparation.',
                'text' => 'The club is holding open training sessions for visitors who want to try team competitions. During the session, participants will set up their stations, check the sound and practise giving teammates short, clear messages. This will be followed by practice matches and a discussion of decisions after each game. To take part, leave a request with the administrator and specify the game you are most interested in.',
            ],
        ];

        DB::transaction(function () use ($translations) {
            foreach ($translations as $originalTitle => $englishContent) {
                $blog = Blog::where('title', $originalTitle)->first();

                if ($blog === null) {
                    throw new RuntimeException("Не знайдено новину: {$originalTitle}. Спочатку виконайте BlogSeeder.");
                }

                foreach ([
                    'uk' => $blog->only(['title', 'description', 'text']),
                    'en' => $englishContent,
                ] as $locale => $content) {
                    BlogTranslation::updateOrCreate(
                        ['blog_id' => $blog->getKey(), 'locale' => $locale],
                        $content,
                    );
                }
            }
        });
    }
}
