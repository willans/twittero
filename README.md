#Twittero for ExpressionEngine

We can already hear you thinking “not another Twitter plugin?!” Don’t worry – this is one with a difference.

There were several things that the Twitter Timeline can’t do ‘out of the box’ (including working all of the time):

- Use the OAuth method to pull in tweets.
- Support custom cache expiry time (if you don’t get much traffic, you could even disable the cache).
- I found that the Twitter Timeline sometimes didn’t link certain parts of a Tweet. Sometimes link URLs, usernames and hash-tags were not linked.
- Allow the user to flip between time/date styles (I wanted the ability to flip between date wording like ‘About 3 days ago’ and regular date formatting).
- Offer the ability to disable retweets.

##Features

- Ability to exclude retweets.
- Show replies.
- Custom cache expiry time (and have ability to disable cache during development).
- Custom time formatting as mentioned above.
- Number of tweets to pull in via the API, and number of tweets to show (due to the structure of the Twitter API, and to prevent multiple API requests, this may need to be different depending on the showing replies option as this cannot be exlcluded via the API).
- Custom HTML formatting using standard EE-like template tags, i.e.  {twittero_tweet} {twittero_time}

##Usage:

    <ol>
    {exp:twittero screen_name="willans"
    consumer_key="lDbqIkinxjZlZzSSkVa4Qg"
    consumer_secret="hvBAIee48xu1V8Hil80xLEXhQTaTKzXfzTd6Dwb8OM"
    oauth_token="15032349-vnAAmEe78gOgXDn5OlNaltSkREMbXA9aDNR6QyHzO"
    oauth_token_secret="YqH3rWX0IOSXMEpbpggpT8lx8j44hCnQrcehGpONIpE"}
    <li>{twittero_tweet} {twittero_time}</li>
    {/exp:twittero}
    </ol>

##Required: 

- screen_name
- consumer_key
- consumer_secret
- oauth_token
- oauth_token_secret

##Options (and defaults):

- include_retweets - true, false (default: true)
- show_replies - true, false (default: true)
- cache_limit - In minutes. Set to 0 to disable cache (default: true)
- time_format - Supports both EE and PHP formatted dates (default: %F %m, %Y %g:%i %a)
- worded_times - true, false. If set to true, timestamps will appear as phrases like 'about 3 days ago' for tweets less than 30 days old (default: true)
- tweets_to_grab - If not showing replies, you'll need to grab more tweets than you want to display (default: 10)
- tweets_to_display - Visible tweets in HTML (default: 10)
