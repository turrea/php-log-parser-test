## Instructions

To see the solution in action, you'll have to do the following:
1. run `tar -xvzf logparser.tgz` and [change to] the LogParser directory
2. run `sh init.sh`, this will extract the `astlog.tgz` file and create a `crontab.txt` file
3. edit `emailConfigs` in `run.php` to send "registration_failed" emails to your email address of choice, there could be a better place for this (like a separate config file), but ran out of time
4. run `crontab crontab.txt` to start cronjob

## Notes

If you would like to analyze a custom log file you would have to edit the target in init.sh and re-run steps 2-4, and possibly remove the old cron job.

## Explanation

I'd like to explain the solution a bit here. I know that analyzing logs is common enough that such a tool should be extensible. That is why I incorporated the concept of a parse strategy, which can check a line in the log any way it deems fit and send emails out based on the results. Whenever the log parser reads through the log it will run the current line through all the registered parse strategies, but for our example we only have one parse strategy (registration failed). I'd like to note that in the real world it might be best to do some research on log analyzing programs out there, but for the sake of this exercise I wanted it to be extensible.

I also opted to store parse progress in a file that should be located at `~/.parse_state_json`. In the real world I would definitely be open to a better way of handling this, but the quickest way I could see to do this was to create a json file that the log parser would read from and write to. Keeping track of ip addresses we have seen is tricky, but I opted to use an associative array where if the key exists that means we have sent an email regarding that address before. I believe behind the scenes in php associative arrays are implemented as hash maps and thus should be quick to look up and hopefully not terribly wasteful on space. Thankfully, it would be easier to plug in a more efficient state mechanism (as long as it implemented `ParseStateInterface`).

Also, to identify the problematic lines I used the regex pattern `/Registration from.+ failed for '(.*)'/`. I was originally using a regex related to `NOTICE[23697]`, but then I thought maybe in the future it won't be a notice? And I also thought that maybe the notice code is generic and not specific to registration failure. I know the message is specific to registration failure, though maybe in the future the error message itself will change structure. Ultimately, I decided to go with analyzing the message.

Things I would liked to have done:
- have a nicer file structure, but opted to just have one file with all the library related functionality in it
- put this under version control, if for nothing else but to back up my work, this I really should have done
- place classes into appropriate namespaces