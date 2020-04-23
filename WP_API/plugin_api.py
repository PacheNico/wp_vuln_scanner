import requests
import json
import urllib.request

ALL_VERSION = False #download all version of code available
DEFAULT_LOCATION = "/tmp/testfolder/" #default download folder

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
    "page":1,
    "per_page":250, #250 MAX
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

def buildUrl():
    url = API_URL +"&"+ url_helper("request",query_plugins)[:-1]
    print("URL:"+ url +"\n\n\n---------------------------------------------------")
    return url

def getPluginInfo(plugin):
    print(plugin)

def getPlugins():
    url = buildUrl();
    r = requests.post(url = url)
    response_json = json.loads(r.text)
    json_formatted_str = json.dumps(response_json, indent=2)
    print(json_formatted_str)
    return response_json

def download_from_link(url,name):
    urllib.request.urlretrieve(url,DEFAULT_LOCATION+name)

def downloadPlugins(plugins):
    print('Beginning plugins download')
    for x in plugins['plugins']:
        download_from_link(x['download_link'],x['name']+x['version']+".zip")
        if ALL_VERSION:
            for y in x['versions']:
                download_from_link(x['versions'][y],x['name']+y+".zip")

    print('plugin download complete')



#main
plugins = getPlugins()
downloadPlugins(plugins)
