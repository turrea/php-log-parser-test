# PHP Log Parser Test

PHP programming test I was asked to complete as part of an interview process. The instructions and code have been modified slightly to anonymize and streamline the content.

## Instructions

Create a PHP script that runs via cron every 5 minutes and monitors a log file for a certain pattern. If it finds the pattern it needs to email the results to a specific email address.

The system needs to monitor a log file and when it notices a line like this:

`[2011-05-04 17:05:53] NOTICE[23697] chan_sip.c: Registration from '"01"<sip:01@207.38.104.211>' failed for '118.97.164.147' - Peer is not supposed to register`

It needs to pull out the IP address and email us something that reads:
`IP address 118.97.164.147 is trying to register on host PBX-2.`

These entries happen in the log file, when someone is trying to connect to our Asterisk servers, which shouldn't be happening. We want to know when it happens so we can do something about it.

The log file that this monitors grows throughout the day as new activity flows in.  So, it can contain a lot of activity and can get hundreds of these entries per second.  Since this script would run every 5 minutes to check for new entries, you'll need to come up with some way to know when you've alerted on a particular IP address already.  Because we don't want this emailing us 100 times a day for the same IP.

Assume you don't have access to a database for this project.

## Sample Log File

I was originally provided with a large sample log file by the company conducting the interview. I am not including that file here because there may be some slightly sensitive information in there. Instead, I've added a sample file I used during development for testing.

## Solution

The v1.0.0 tag is very close to the original solution I submitted, but not identical; only minor modifications were made i.e. fixing a few minor typos. My only disclaimer is that I was operating under a time constraint. Although no strict time restriction was requested by the company, I wanted to submit a working solution in a timely fashion as I had a scheduling conflict around the time I was working on this programming problem.

I ended up submitting the code zipped up as `logparser.tgz` along with the notes in `SOLUTION.md`.
