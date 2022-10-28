<?php

namespace App\Service;

use phpDocumentor\Reflection\DocBlock\Tags\Throws;

class QuoteService
{
    private array $quotes = [
        "“But real evil has to be dealt with, and you don’t do that by letting it live to take good people down.”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“I guess marriage isn’t built for secrets.”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“I don’t want any of this. All I want is you.”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“You made me feel like you really saw me.”\n — <em>Love Quinn</em>, <b>You</b>",
        "“The end of our story remains unwritten.”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“I barely recognize myself anymore.”\n — <em>Love Quinn</em>, <b>You</b>",
        "‘When I go…will you come with me?”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“Marriage is a game.”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“The more I know, the less I understand.”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“Why is male small talk so terrible?”\n — <em>Joe Goldberg</em>, <b>You</b>",
        "“It's your game, take it.”\n — <em>Vasily Borgov</em>, <b>The Queen's Gambit</b>, Limited Series: End Game",
        "“For a time, I was all you had. And for a time, you were all I had.”\n — <em>Jolene</em>, <b>The Queen's Gambit</b>, Limited Series: End Game",
        "“I would say that it's much easier to play chess without the burden of an Adam's Apple.”\n — <em>Beth Harmon</em>, <b>The Queen's Gambit</b>, Limited Series: Adjournment",
        "“You play what's best for you.”\n — <em>Benny Watts</em>, <b>The Queen's Gambit</b>, Limited Series: Adjournment",
        "“It takes a strong woman to stay by herself.”\n — <em>Alice Harmon</em>, <b>The Queen's Gambit</b>, Limited Series: Adjournment",
        "“<em>Benny Watts</em>: You always drink this much?\n<em>Beth Harmon</em>: Sometimes, I drink more.”\n— <em>Beth Harmon</em>, <b>The Queen's Gambit</b>, Limited Series: Fork",
        "“Anger is a potent spice.”\n— Harry Beltik, <b>The Queen's Gambit</b>, Limited Series: Fork",
        "“Intuition can't be found in books.”\n— <em>Alma Wheatley</em>, <b>The Queen's Gambit</b>, Limited Series: Middle Game",
        "“Chess is not all there is.”\n— <em>Alma Wheatley</em>, <b>The Queen's Gambit</b>, Limited Series: Middle Game",
        "“I don't know why my body is so intent on sabotaging my brain, when by brain is capable of sabotaging itself.”\n— <em>Alma Wheatley</em>, <b>The Queen's Gambit</b>, Limited Series: Doubled Pawns",
        "“It's an entire world of just 64 squares. I feel safe in it. I can control it, I can dominate it. And it's predictable.”\n— <em>Beth Harmon</em>, <b>The Queen's Gambit</b>, Limited Series: Doubled Pawns",
        "“I'm glad you finally found someone that treated you right.”\n— <em>Allison Hargreeves</em>, <b>The Umbrella Academy</b>, Season 3: Meet the Family",
        "“The only thing <b>The Umbrella Academy</b> knows about love is how to screw it up.”\n— <em>Klaus Hargreeves</em>, <b>The Umbrella Academy</b>, Season 2: Valhalla",
        "“We don't live in a universe of rules, we live in a universe of chances.”\n— <em>Sir Reginald Hargreeves</em>, <b>The Umbrella Academy</b>, Season 2: Valhalla",
        "“No one gets to tell us how to deal with the end of the world.”\n— <em>Luther Hargreeves</em>, <b>The Umbrella Academy</b>, Season 2: The Majestic 12",
        "“We don't have to understand shit about for it to be real.”\n— <em>Diego Hargreeves</em>, <b>The Umbrella Academy</b>, Season 2: The Frankel Footage",
        "“They're gone like a fart in the wind.”\n— <em>Klaus Hargreeves</em>, <b>The Umbrella Academy</b>, Season 2: Right Back Where We Started",
        "“Ordinary is not a word I would use to describe you.”\n— L<em>Leonard Peabody</em>, <b>The Umbrella Academy</b>, Season 1: The Day That Wasn't",
        "“Everyone I like is already dead.”\n— <em>Diego Hargreeves</em>, <b>The Umbrella Academy</b>, Season 1: The Day That Wasn't",
        "“Okay, it’s official. I’m never having kids.”\n— <em>Dustin Henderson</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter One",
        "“She’s hotter than Phoebe Cates.”\n— <em>Dustin Henderson</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter One",
        "“Erica: You can’t spell America without Erica.\nDustin: Yeah, oddly that’s true.”\n— <em>Erica Sinclair</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter Four",
        "“Yeah, the real world sucks, deal with it like the rest of us.”\n— <em>Jonathan Byers</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter Four",
        "“It’s this stupid hat. I am telling you, it’s totally blowing my best feature.”\n— <em>Steve Harrington</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter One",
        "“Steve, talking about Dustin: He’s missing bones and stuff. He can bend like Gumbo.\nRobin: You mean Gumby.\nSteve: No, I’m pretty sure it’s Gumbo.”\n— <em>Steve Harrington</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter Four",
        "“You know what this half-baked plan of yours sounds like to me? Child endangerment.”\n— <em>Erica Sinclair</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter Four",
        "“Feelings. The truth is, for so long, I'd forgotten what those even were. I've been stuck in one place, in a cave, you might say. A deep, dark cave.”\n— <em>Jim Hopper</em>, <b>Stranger Things</b>, Stranger Things 3: Chapter Eight",
        "“It’s gonna be okay. Remember, Bob Newby, superhero.”\n— <em>Bob Newby</em>, <b>Stranger Things</b>, Stranger Things Season 2: Chapter Eight",
        "“She will not be able to resist these pearls.”\n— <em>Dustin Henderson</em>, <b>Stranger Things</b>, Stranger Things Season 2: Chapter One",
        "“This thing has had Will long enough. Let’s kill the son of a bitch.”\n— <em>Joyce Byers</em>, <b>Stranger Things</b>, Stranger Things Season 2: Chapter Nine",
        "“This is not a normal family.”\n— <em>Joyce Byers</em>, <b>Stranger Things</b>, Stranger Things Season 2: Chapter Two",
        "“I may be a pretty shitty boyfriend, but turns out I’m actually a pretty damn good babysitter.”\n— <em>Steve Harrington</em>, <b>Stranger Things</b>, Stranger Things Season 2: Chapter Nine",
        "“Shall I teach you French while I’m at it, Jim? How about a little German?”\n— <em>Bob Newby</em>, <b>Stranger Things</b>, Stranger Things Season 2: Chapter Eight",
        "“So, Jonathan, how was the pull-out?”\n— <em>Murray Bauman</em>, <b>Stranger Things</b>, Stranger Things Season 2: Chapter Six",
        "“Mummies never die, so they tell me.”\n— <em>Jim Holden</em>, <b>Stranger Things</b>, Stranger Things Season 1: Chapter One",
        "“Claire: I never believed in the tooth fairy.\nElizabeth: Well, you took the money anyway.”\n— <em>Claire Underwood</em>, <b>House of Cards</b>, Season 4: Chapter 49",
        "“Brave is never giving up. You fight, no matter what.”\n— <em>Hannay Conway</em>, <b>House of Cards</b>, Season 4: Chapter 47",
        "“Do you want to live? Tell us what we want. Tell us what we want to hear.”\n— <em>Frank Underwood</em>, <b>House of Cards</b>, Season 5: Chapter 53",
        "“After a dog’s bitten you, you either put it to sleep, or you put a muzzle on it. I’ve chosen a muzzle...for now.”\n— <em>Frank Underwood</em>, <b>House of Cards</b>, Season 4: Chapter 50",
        "“Claire: You said once you thought he was all surface, Conway.\nYates: Yeah, a narcissist. But give him a pool to reflect in...watch out.”\n— <em>Claire Underwood</em>, <b>House of Cards</b>, Season 4: Chapter 51",
        "“He was right about your soul. What’s in your bones. That you’re ruthless. You’re corrupt. You destroy whatever’s in your path.”\n— <em>Tom Hammerschmidt</em>, <b>House of Cards</b>, Season 4: Chapter 52",
        "“I hope he remembers everything. So that when I stand in front of him, he knows I played my part.”\n— <em>Dinah Madani</em>, <b>Marvel's The Punisher</b>, Season 1: Memento Mori",
        "“People think that torture is pain. It’s not pain. It’s time.”\n— <em>Frank Castle</em>, <b>Marvel's The Punisher</b>, Season 1: Kandahar",
        "“Pissed off beats scared every time.”\n— <em>Frank Castle</em>, <b>Marvel's The Punisher</b>, Season 1: Resupply",
        "“If you’re gonna look at yourself, really look in the mirror, you gotta admit who you are. But not just to yourself, you gotta admit it to everybody else.”\n— <em>Frank Castle</em>, <b>Marvel's The Punisher</b>, Season 1: Memento Mori",
        "“Shit is a lot easier when you can kill people.”\n— <em>Frank Castle</em>, <b>Marvel's The Punisher</b>, Season 1: Crosshairs",
        "“The only way to get by in this world is to step off it for a while.”\n— <em>Billy Russo</em>, <b>Marvel's The Punisher</b>, Season 1: Gunner",
        "“Some of us get to have the family life and some of us get to protect it.”\n— <em>Dinah Madani</em>, <b>Marvel's The Punisher</b, Season 1: Two Dead Men",
        "“I’m not gonna ask you if you just said what I think you just said because I know it’s what you just said.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 3, episode 12 “War of Coprophages.”",
        "“The truth is out there, but so are lies.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 1, episode 17 “E.B.E.”",
        "“I want you to do me a favor. It’s not negotiable, either you do it, or I kill you. You understand?”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 6, episode 3 “Triangle”",
        "“Baby' me and you'll be peeing through a catheter.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 6, episodes 4 & 5 “Dreamland”",
        "“Nothing happens in contradiction to nature, only in contradiction to what we know of it.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 4, episode 1 “Herrenvolk”",
        "“I don’t have time for your convenient ignorance.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 3, episode 9 “Nisei” & 10 “731”",
        "“You know, I haven't eaten since 6:00 this morning, and all that was was a half a cream cheese bagel, and it wasn't even real cream cheese, it was light cream cheese!”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 5, episode 12 “Bad Blood”",
        "“SURE. FINE. WHATEVER.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 3, episode 13 “Syzygy”",
        "“I want to remember how it was. I want to remember how it all was.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 11, episode 4 “The Lost Art of Forehead Sweat”",
        "“Please explain to me the scientific nature of the whammy.”\n— <em>Dana Scully</em>, <b>The X-Files</b>, Season 3, episode 17 “Pusher”",
        "“How they police us and spy on us, tell us that makes us safer? We’ve never been in more danger.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“You’re never “just” anything to me, Scully.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“You see a man lying here, a seemingly weak man, but I’m the most powerful man in the world.”\n— <em>Cigarette Smoking Man</em>, <b>The X-Files</b>",
        "“What if there was only one choice and all the other ones were wrong? And there were signs along the way to pay attention to.”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
        "“There’s a lot of money to be made in scaring people.”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
        "“You want the truth, Agent Mulder? You’ve come to the right place.”\n— <em>Cigarette Smoking Man</em>, <b>The X-Files</b>",
        "“We’ve been given another case, Mulder. It has a monster in it.”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
        "“Mulder, the Internet is not good for you.”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
        "“How they police us and spy on us, tell us that makes us safer? We’ve never been in more danger.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "““Back in the day.” Scully, “back in the day” is now.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“Your own government lies as a matter of course, as a matter of policy…”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“Who needs Google when you got Scully?”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“We must ask ourselves… are they really a hoax? Are we truly alone? Or are we being lied to?”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“Sometimes the only sane answer to an insane world is insanity.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“I’ve often felt that dreams are answers to questions we haven’t yet figured out how to ask.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“I’ve controlled you since before you knew I existed.”\n— <em>Cigarette Smoking Man</em>, <b>The X-Files</b>",
        "“I’m old-school, Mulder. Pre-Google.”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
        "“I have never met anyone so passionate and dedicated to a belief as you. It’s so intense that sometimes it’s blinding.”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
        "“Alien technology being used against us, not by aliens, not with aliens, but by a venal conspiracy of men against humanity.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“Why is your house so much nicer than mine? <i>[to Scully]</i>”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“Well, I did not burst into flames when I crossed the threshold, so I guess they really do forgive a lot.<i>[to Scully, after walking into a church]</i>”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
        "“We have a small problem. They’ve reopened the X-Files.”\n— <em>Cigarette Smoking Man</em>, <b>The X-Files</b>",
        "“This is my problem with modern-day monsters, Scully. There’s no chance for emotional investment.”\n— <em>Fox Mulder</em>, <b>The X-Files</b>",
        "“We do our work. The truth still lies in the X-Files, Mulder.”\n— <em>Dana Scully</em>, <b>The X-Files</b>",
    ];

    public function getSerieQuotes(): array
    {
        $max = count($this->quotes) - 1;
        $indexes = [];
        do {
            $new = rand(0, $max);
            if (!in_array($new, $indexes)) {
                $indexes[] = $new;
            }
        } while (count($indexes) < 4);

        return [
            $this->quotes[$indexes[0]],
            $this->quotes[$indexes[1]],
            $this->quotes[$indexes[2]],
            $this->quotes[$indexes[3]],
        ];
    }

    public function getRandomQuotes(): ?array
    {
        return $this->getSerieQuotes();
    }

    public function getRandomQuote(): ?string
    {
        return $this->quotes[rand(0, count($this->quotes) - 1)];
    }
}