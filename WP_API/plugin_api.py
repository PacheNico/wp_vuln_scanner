import requests
import json
import urllib.request
import threading
import os
import subprocess

ALL_VERSION = False #download all version of code available
DEFAULT_LOCATION = "/tmp/testfolder" #default download folder

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

#params structure
query_plugins = {
    "browse":"popular",
    "search":"",
    "tag":"",
    "author":"",
    "per_page":10, #250 MAX
    "fields":fields
}

def url_helper(parentlist,dictionary):
    param = ""
    for key, value in dictionary.items():
        if type(value) is dict:
            param= param+ url_helper(parentlist+"["+key+"]",value)
        else:
            param= param+ parentlist+"["+str(key)+"]="+str(value)+"&"

    return param

def buildUrl(page_num=1):
    url = API_URL +"&"+ url_helper("request",query_plugins)+"request[page]="+str(page_num)
    #print("URL:"+ url +"\n\n\n---------------------------------------------------")
    return url


def getPlugins(page_num=1):
    url = buildUrl(page_num);
    r = requests.post(url = url)
    response_json = json.loads(r.text)
    json_formatted_str = json.dumps(response_json, indent=2)
    #print(json_formatted_str)
    return response_json

def download_from_link(url,name,loc):
    try:
        urllib.request.urlretrieve(url,loc+name)
    except:
        print("download failed: "+name)

def downloadPlugins(plugins,download_loc):
    print('Beginning plugins download')
    for x in plugins['plugins']:
        download_from_link(x['download_link'],x['name']+x['version']+".zip",download_loc)
        if ALL_VERSION:
            for y in x['versions']:
                download_from_link(x['versions'][y],x['name']+y+".zip",download_loc)

    print('plugin download complete')

def getPageCount():
   return getPlugins()["info"]["pages"]

def threading_process(page_num):
    plugins= getPlugins(page_num)
    download_loc = DEFAULT_LOCATION +"/"+str(page_num)+"/"
    
    if not os.path.exists(download_loc):
        os.makedirs(download_loc)

    downloadPlugins(plugins,download_loc);
    unpack = subprocess.check_call("./unpack '%s'" % download_loc,shell=True)
    print("done thread"+ str(page_num))

def thread_start(page_cnt):

    if not os.path.exists(DEFAULT_LOCATION):
        os.makedirs(DEFAULT_LOCATION)

    print(str(page_cnt))
    batches =  page_cnt//5
    remainder = page_cnt%5
    for i in range(batches):
        
        print("batch"+str(i))
        batch_page_start = 5*i
        
        
        t1 = threading.Thread(target=threading_process, args=(batch_page_start+1,))
        t2 = threading.Thread(target=threading_process, args=(batch_page_start+2,))
        t3 = threading.Thread(target=threading_process, args=(batch_page_start+3,))
        t4 = threading.Thread(target=threading_process, args=(batch_page_start+4,))
        t5 = threading.Thread(target=threading_process, args=(batch_page_start+5,))
        
        
        t1.start();
        t2.start();
        t3.start();
        t4.start();
        t5.start();
        
        
        t1.join()
        t2.join()
        t3.join()
        t4.join()
        t5.join()

#main
page_cnt = getPageCount()
thread_start(page_cnt)
