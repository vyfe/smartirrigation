/**
 * Created by lvxinwei on 16/4/15.
 */
var Kit = function () {
    var that = this;
    this.version = "1.0 ";
    this.ret=null;
    /**
     * ajax封装
     * @param url
     * @param param
     * @param callback
     */
    this.query=function(url,param,callback){
        $.ajax({
            type: "POST",
            cache: true,
            data: param,
            url:url,
            success: function (data) {
                callback(data);
            }
        });
    }

};