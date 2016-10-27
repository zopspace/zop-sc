var flowPlayerApi;
var lastVidInd=0;

var PlaylistController = {
    initFlowPlayer: function () {
        //flowPlayerApi = 
    },
    loadPlaylist: function () {
        var playlist = PlaylistController.populatePlaylist();
        var container = $(".flowplayer");
        flowPlayerApi = flowplayer(container, {
            autoplay: true,
            // clip: clip
            playlist: playlist
        });

        flowPlayerApi.on("finish", function (e, current_vid) {
            //console.log("video finished. load next one here.");
            if (!current_vid.video.is_last) {
                $.notify("Current video is finished. Loading next video", 
                {className: "info", position:"bottom right"});
                
                var vidIndex = current_vid.video.index;
                //var vidSrc = current_vid.video.src;
                $("#vidd" + vidIndex).removeClass("is_active");
                
            } else {
                $.notify("Playlist finished.", {className: "info", position:"bottom right"});
            }
        });
        
        flowPlayerApi.on("ready", function(e, current_vid){
           //console.log(current_vid.video.index +" is ready to play."); 
           lastVidInd = current_vid.video.index;
           
           var vidObj = $("#vid"+current_vid.video.index);
           var vidLiObj = $("#vidd"+current_vid.video.index);
           vidLiObj.addClass("is_active");
           var vidTitle = vidLiObj.find("p.header90").html();
           //console.log("vidTitle:"+vidTitle);
           //set video title in h3 tag
           $("#vidTitle").html(vidTitle);
           $("#frm_video_id").val(vidObj.data("vidid")); //form element
           $("ul#myList > li").hide();
           //console.log($("#li-"+current_vid.video.index))  ;  
           $("#li-"+current_vid.video.index).show(); 
           //initiate the discussion loading for this video
           DiscussionController.loadDiscussion(vidObj.data("vidid"));   
            
           //set video tags
           var vidTag = vidObj.data("tags");
           var vidTags = vidTag.split(",");    
           var tagsDiv = '';
           var num = 0;
           var limit = 15;
           for(var i = 0; i < vidTags.length; i++){
               if ( limit > num){
                   num += 1;
               } else {
                   num -= 14;
               }
               tagsDiv += '<div class="tagLabel tag' + num + '">' + vidTags[i] + '</div>';
           }
           $(".tagbar").html(tagsDiv);
        });
    },
    playVideo: function(videoIndex){
       console.log("Playlist should now load the video on index: " + videoIndex);
       if ( flowPlayerApi ){
           $("#vidd" + lastVidInd).removeClass("is_active");
           flowPlayerApi.play(videoIndex);
       } else {
          $.notify("Player is not loaded yet", {className: "info", position:"bottom right"});
       }
    },
    attachVideoEvent: function(){
        $(".dummyvid").on('click', function(event){
             var vidlink = $(this);
             var vidind = vidlink.data("index");
             PlaylistController.playVideo(vidind);
        });
    },
    populatePlaylist: function () {
        var playlist = [];
        $(".single-right-grids").each(function (index) {
            //console.log($(this));
            var vidURL = $(this).data("url");
            
            //because of the change in the layout. following line would not work for us.
            //var vidTitle = $(this).find("a.title").html();
            var parent_li_tag = $(this).parent();
            //console.log(parent_li_tag);
            var vidTitle = parent_li_tag.find("p.header90").html(); 
            //console.log(vidTitle);
            playlist.push({
                sources: [{
                    type: "video/mp4",
                    src: vidURL
                    }],
                title: vidTitle
            });
        });
        return playlist;
    },
    loadVideo: function (songobj) {
        var song = $(songobj);
        //$.notify("Load " + song.data("url"), "info");
    },

    /***** functions to get events for achso player. didn't work so far ********/
    load: function () {
        //window.postMessage();
        dplayer = document.getElementsByTagName("iframe");
        if (dplayer) {
            dplayer = dplayer[0];
            console.log(dplayer.contentWindow);
            console.log(dplayer.contentDocument);
            PlaylistController.bindListener(dplayer);
        } else {
            console.log("No iframe found");
        }
    },
    bindListener: function (player) {
        //listen for messages from player
        console.log("binding listener");
        if (window.addEventListener) {
            player.contentWindow.addEventListener('message', PlaylistController.receiveEventMessage, false);
        } else {
            player.contentWindow.attachEvent('onmessage', PlaylistController.receiveEventMessage, false);
        }
    },
    receiveEventMessage: function (event) {
        console.log("Event origin: " + event.origin);
        var data = parseEventData(event.data);
        console.log("Event data: " + data);
    },
    checkIframeLoading: function () {
        console.log("checking iframe loading..");
        $("#plarea").on("load", function () {
            console.log("iframe loaded fully. now attach handlers");
            PlaylistController.load();
        });

        /*var iframe = document.getElementById('plarea');
        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        */
    }
};