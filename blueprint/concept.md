/** Copilot ignore this file
  * This is a raw concept file where I am jotting down my thoughts before confirming on it.
  * This is so that I myself have point of reference for the decisions I have made.
  * We will have a multi project architecture,
 */

User access the webapp and they see a dashboard page. The dashboard page gives hilight for the day in general. It shows the expense history for the rolling 7 days vs the average for that day or (togglable) rolling 30 days vs the average for that date. (oh boy, leap year is going to be fun). Dashboard will also show what is the most popular item that was purchased.



The calender will be supported in 2 formats, AD and BS. We beed to build our own calendar engine. read https://github.com/dantwoashim/Project_Parva for inspiration. The calendar will be one of the core features of the app, and a lot of other features will be dependent on it.
We will need 

so i was thinking that in a calender users can plan anything
- milestone: This is simple high level planning that does not go into day to day details. For example launching a app. There is two ways:
    - Fixed milestone: user is sure when they would like to start, and when they would like to have it done by. The deadline/enddate can be 
    - Flexible milestone: 

If the milestone comes with hard deadline or if it is softdeadline. This would be a start date and end date kind of thing which will not have any starttime or endtime involved meaning whole day event throughout. Alternatively a milestone can have start date 
- schedule: this is something that is very closely related to times. There could be schedule such as go to bed before 9PM, wakeup at 6AM and so on. some schedules my not occour on certain days such as monday to friday 9-5 at work but not sat - sun, bank holiday, on leave(annual, sick,...). The schedule could be different for sat - sun compared to  mon - fri or for specific dates. When editing the schedule we need to confirm if it is only for that specific occurance or is it for all occurance after this. When creating a schedule go to work, user can set start and end time, can chose the days they want this to occour at, set exception on specific day of start and end is different such as friday myght be early end

There could be weekly schedule such as pay your staff on friday every week. There could be monthly schedule such as pay your rent first working day of every month

### Questions

- is it worth using timeseries database for our case?
