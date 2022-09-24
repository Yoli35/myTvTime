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

    public function getRandomQuote(): ?String
    {
        return $this->quotes[rand(0, count($this->quotes))];
    }
}