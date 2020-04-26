import requests
import json
import urllib.request
import concurrent.futures
import os

# configurable variables
ALL_VERSION = False         #download all version of code available
DEFAULT_LOCATION = "/tmp/testfolder"  #default download folder
THREAD_COUNT=7 #threads in threadpool
PLUGINS_PER_BATCH = 75 #number of plugins per page 
API_URL = "http://api.wordpress.org/plugins/info/1.1/?action=query_plugins";

#fields returned by API (SOME ALWAYS ON)
fields = {
        "name" : 0,
        "author" : 0,
        "slug" : 0,
        "downloadlink" : 0,
        "rating" : 0,
        "ratings" : 0,
        "downloaded" : 0,
        "description" : 0,
        "active_installs" : 0,
        "short_description" : 0,
        "donate_link" : 0,
        "tags" : 0,
        "sections" : 0,
        "homepage" : 0,
        "added" : 0,
        "last_updated" : 0,
        "compatibility" : 0,
        "tested" : 0,
        "requires" : 0,
        "versions" : 0,
        "support_threads" : 0,
        "support_threads_resolved" : 0

}

#REQUEST structure
query_plugins = {
    "browse":"popular",
    "search":"",
    "tag":"",
    "author":"",
    "per_page":PLUGINS_PER_BATCH, #250 MAX
    "fields":fields
}

#helps builds the url based on the query plugin and fields variable 
def url_helper(parentlist,dictionary):
    param = ""
    for key, value in dictionary.items():
        if type(value) is dict:
            param= param+ url_helper(parentlist+"["+key+"]",value)
        else:
            param= param+ parentlist+"["+str(key)+"]="+str(value)+"&"

    return param

# returns the url need to get a page of plugins
def buildUrl(page_num=1):
    url = API_URL +"&"+ url_helper("request",query_plugins)+"request[page]="+str(page_num)
    return url

#makes a post request to the API and returns the list of plugins 
def getPlugins(page_num=1):
    url = buildUrl(page_num);
    r = requests.post(url = url)
    response_json = json.loads(r.text)
    return response_json

#downloads from a url to the loc folder with the given name
def download_from_link(url,name,loc):
    try:
        urllib.request.urlretrieve(url,loc+name)
    except:
        print("download failed: "+name) #need to fix issue of wierd characters in some plugin names 

#downloads all the plugins in the returned list of plugins 
def downloadPlugins(plugins,download_loc):
    print('Beginning plugins download')
    for x in plugins['plugins']:
        download_from_link(x['download_link'],x['name']+x['version']+".zip",download_loc)
        if ALL_VERSION:
            for y in x['versions']:
                download_from_link(x['versions'][y],x['name']+y+".zip",download_loc)

#gets the number of pages in the WP API
def getPageCount():
   return getPlugins()["info"]["pages"]

#defines the process needed to analyze a page of the plugins
def threading_process(page_num):
    plugins= getPlugins(page_num)
    download_loc = DEFAULT_LOCATION +"/"+str(page_num)+"/"
    
    if not os.path.exists(download_loc):
        os.makedirs(download_loc)

    downloadPlugins(plugins,download_loc);
    unpack = subprocess.check_call("./unpack '%s'" % download_loc,shell=True)
    print("done thread"+ str(page_num))

#thread pool enviornment that starts the batches 
def thread_start(page_cnt):

    if not os.path.exists(DEFAULT_LOCATION):
        os.makedirs(DEFAULT_LOCATION)

    print("starting thread pool. queue length:"+str(page_cnt))
    with concurrent.futures.ThreadPoolExecutor(max_workers=THREAD_COUNT) as threadPool:
        for page in range(page_cnt):
            threadPool.submit(threading_process,page)


#main
page_cnt = getPageCount()
thread_start(page_cnt)
