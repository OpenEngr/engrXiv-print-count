# engrXiv-print-count

This is a cumulative count of engrXiv prints posted over time. The data is collected using the PHP script (modified from [here](https://bitbucket.org/octogroup/osf-preprint-list)).

## To use the PHP script
Use the OSF's API (https://developer.osf.io/) to gather a list of papers in engrXiv so we can gather stats.

You will need an Authorization Token (https://developer.osf.io/#tag/Authentication) to make requests.

We use the PHP Curl Class library to handle our GET requests: https://github.com/php-curl-class/php-curl-class

Put your authorization token where it says `{YourAuthorizationTokenHere}`, without the curly brackets.


## The Jupyter Notebook
The Jupyter notebook is just a small bit of Python to plot the results that are stored in the CSV file generated by the PHP script. The current data file used in the notebook is dated 2018-09-30 and is hosted here: https://osf.io/5mp82/
