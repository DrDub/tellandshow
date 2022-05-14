# Tell-and-Show: Community Content-sharing Without Idols Nor Masters

With so many software systems with machine learning these days, the data used to train the models is as important, if not more important, as the source code. While the data comes from the community, it is kept private by the entities that collect it, giving them a competitive advantage impossible to match by the Free Software movement. What can we do about it? Tell-and-Show is an experiment using Free Software licenses for data collection.

Tell-and-Show is a project for open recommendations. By sharing preference data to the community, contributors can help build a preference metric which can then be used to provide private recommendations in the browser.

What is special about Tell-and-Show is the use of the AGPLv3 license to protect **data** and to consider said data as the source for machine learning models. The license thus means that anybody interacting with the model should have access to the original data from which the model is derived. Moreover, if the data was enriched with additional data, the extra data should also be released by virtue of the "virality" of the GPL.


## Current Stage

The `process` folder contains the initial preprocess from a Wikimedia Commons dump to key data for annotation.

The `site` folder contains the current website and the active learning annotation tool.

See the [website](http://tellandshow.org/about_en.html) for further information.

## Roadmap

In the [website](http://tellandshow.org/roadmap_en.html).

