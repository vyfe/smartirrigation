/**
 * Created by lvxinwei on 2016/5/5.
 */
$(document).ready(function(){
    var params={};
    window.location.search
        .replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str,key,value) {
            params[key] = value;
        }
    );
    $("#search").click(function(){
        getParams();
        url=window.location.origin+window.location.pathname+"?"+ $.param(params);
        window.location.href=url;
    })
    $("#export").click(function(){
        getParams();
        url=window.location.origin+"/download/weather_export"+"?"+ $.param(params);
        window.location.href=url;
    })

    function getParams(){
        $(".search").each(function(){
            name=$(this).attr("name")
            params[name]=$(this).val()
        })

    }
})