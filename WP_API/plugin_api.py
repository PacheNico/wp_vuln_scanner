import requests
import json


API_URL = "http://api.wordpress.org/plugins/info/1.0/?action=query_plugins";

fields = {
    "description": False, 
    "sections": False, 
    "tested": False,
    "requires": False,
    "rating": False,
    "downloaded": True,
    "downloadlink": True,
    "last_updated": False,
    "homepage": False,
    "tags": False
}
query_plugins = {
    "browse":"popular",
    "search":"video-popup-block",
    "tag":"",
    "author":"",
    "page":1,
    "per_page":100,
    "fields":fields
    }



plugin_information = {
    "slug":"",
    "fields":fields
}

def getPluginInfo(plugin):
    print(plugin)

def getPlugins():
    r = requests.post(url = API_URL, data = json.dumps(query_plugins)) 
    print("request response:%s"%r.text) 

getPlugins()