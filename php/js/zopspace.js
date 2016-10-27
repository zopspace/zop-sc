var ZoPVars = {};
var authorEvent = 0;

var ZopSpaceGlobal = {
    SortOpt: "view",
};

var ZopSpace = {
    UPDATE_AUTHOR_CTRL: false,
    loadPubVideos: function(){
        var achrailsUri = "http://zop.space/sc/php/zopspace.php";
        Pace.restart();
        //Pace.track(function(){
        $.ajax({
                url:achrailsUri, 
                //crossOrigin: true,
                dataType: "json",
                success: function(response){
                    console.log(response);
                    if ( response ){
                        ZoPVars.videos = response.videos;
                        //random shuffling
                        ZoPVars.videos = ZopSpace.shuffleArray(ZoPVars.videos);
                        ZopSpace.UPDATE_AUTHOR_CTRL = true;
                        ZoPVars.videos.forEach(ZopSpace.buildVideoTag);
                        ZopSpace.UPDATE_AUTHOR_CTRL = false;
                        //now update the authorList control on search panel
                        ZopSpace.sortAuthorListCtrl();
                        //set the default selection for author
                        //$('select#author_dropdown option[value=""]').attr("selected",true);
                        $('#author_dropdown').val('');
                        
                        //attach author popup event
                        ZopSpace.authorPopupEvent();
                        //attach single video playing event
                        ZopSpace.playVideoEvent();
                    }
                },
                error: function(xhr, code, reason){
                    console.log(code+":"+reason);
                }
        });
           // });
    },
    //using Fisher-Yates algorithm taken from http://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array
    shuffleArray: function(array) {
        var currentIndex = array.length, temporaryValue, randomIndex;
        // While there remain elements to shuffle...
    
        while (0 !== currentIndex) {

            // Pick a remaining element...
            randomIndex = Math.floor(Math.random() * currentIndex);
            currentIndex -= 1;
            // And swap it with the current element.
            temporaryValue = array[currentIndex];
            array[currentIndex] = array[randomIndex];
            array[randomIndex] = temporaryValue;
        }
        return array;
    },
    resetForm: function(){
      $('#author_dropdown').val('');
      $('#tagList').val('');
      $('#titlecontains').val('');
      $('#txtcontains').val('');
    },
    playVideoEvent: function(){
       
       $('.dumvid').on('click', function(event){
           event.preventDefault();
           var vidLink = $(this);
           var id = vidLink.data("vid");
           console.log("should send this video id for playing: " + id);
           //now create a dynamic form and submit
           
           $('<form>', {'action':'php/zopspace_search2.php',
            'method':'post',
            'html':'<input type="hidden" name="videoId" value="'+id+'"/>'
            }).appendTo(document.body).submit();
       }); 
    },
    search: function(){
      //http://defiantjs.com/
        //searchable property contains title and annotations combined together. so for title and annotation search, use this field
        //owner is also part of searchable property. so we can combined them all in one field
        //We can also do Interaction for AND query on multiple attributes and Union for OR query on multiple attributes.
        //It is to be discussed that how these parameters are searched.
        var titleIn = $("#titlecontains").val();
        var annIn = $("#txtcontains").val();
        var ownerIn = $("#author_dropdown").val();
        
        titleIn = titleIn.trim().toLowerCase();
        annIn = annIn.trim().toLowerCase();
        ownerIn = ownerIn.toLowerCase();
        
        var srchResult = ZoPVars.videos.filter(function(video){ 
            var searchable = video.searchable.toLowerCase();
            
            if ( (titleIn && searchable.search(titleIn) >= 0 ) 
                || (annIn && searchable.search(annIn) >=0 ) 
                || (ownerIn && searchable.search(ownerIn) >=0 ) 
               )
                return true;
            
            return false;
        });
        
        var sortType = $("#orderCtrl").val();
        ZopSpace.resultSort(srchResult, sortType);
        $("#horizontal-style").empty();
        srchResult.forEach(ZopSpace.buildVideoTag);
        
        return false;
    },
    resultSort: function(list, opt){
        //console.log("inside alphabetical sorting");
        if ( opt == 'alpha') {
            list.sort(function ( vid1, vid2 ){
                var t1 = vid1.title.toLowerCase(), t2 = vid2.title.toLowerCase(); 
                return t1 > t2 ? 1 : t1 < t2 ? -1 : 0;
            });
        }
        else if ( opt == 'view' ) {
            list.sort (function(vid1, vid2){
               return vid2.views - vid1.views; 
            });
        }
        else if ( opt == 'recent') {
            list.sort ( function (vid1, vid2) {
               d1 = new Date(vid1.created_at);
               d2 = new Date(vid2.created_at);
               return d2 - d1;
            });
        }
        else if ( opt == 'like')
            $.notify("Like data is not available for sorting", "error");
    },
    
    sortAuthorListCtrl: function(){
      var authorList = $("#author_dropdown option");  
      authorList.sort(function(obj1, obj2){
         var t1 = obj1.value.toLowerCase(), t2 = obj2.value.toLowerCase();
         return t1 > t2 ? 1 : t1 < t2 ? -1 : 0;
      });
      $("#author_dropdown").html(authorList);
        
    }, 
    
    sortVideos: function(sortType){
        ZopSpaceGlobal.SortOpt = sortType;
        
        if ( ZopSpaceGlobal.SortOpt == 'view'){
            ZoPVars.videos.sort (function(vid1, vid2){
               return vid2.views - vid1.views; 
            });
        }else if (ZopSpaceGlobal.SortOpt == 'time'){
            
        }
        
        //re-render videos
        $("#horizontal-style").empty();
        ZoPVars.videos.forEach(ZopSpace.buildVideoTag);
        return false;
    },
    
    submit: function(){
        var formT = $("#search_form");
        var formdata = formT.serialize();
        console.log(formdata);
        formT.submit();
    },
    buildVideoTag: function( video ){
        //console.log(video);
        var imgHeight = '';
       // console.log(video.manifest_json.rotation+":"+video.title);
        /*if ( video.manifest_json.rotation == 90 || video.manifest_json.rotation === 0){
            imgHeight = 'height="210"';
        }*/
        //width=280 
        var videoElement = '<li class="col-md-3 col-sm-3 col-xs-3 resent-grid recommended-grid" data-id="id-'+video.id+'" data-type="money act">' +
        	'<div class="js-video-bg">' +
				'<div data-orientation="'+video.manifest_json.rotation+'">' +
				    //'<a href="' + video.manifest_json.videoUri + '"><img class="gridimage" src="' + video.manifest_json.thumbUri + '" alt="video"></a>' +
				    '<a class="dumvid" data-vid="'+video.uuid+'" href="#"><img class="gridimage" src="' + video.manifest_json.thumbUri + '" alt="video"></a>' +
				'</div>' + 
			'</div>' + 
			//	'<div class="duration">' +
			//	    '<p>Duration: 2:34</p>' + 
			//	'</div>' +
			//	'<div class="views">' +
			//	    '<p>' + video.views + ' Views</p>' + 
			//	'</div>' +
				'<div class="resent-grid-info recommended-grid-info">' + 
					'<div class="titler">' +
                    	'<p class="header9">' + video.title + '</p>' +
                    '</div>' +
                    	'<h5>' + video.description + '</h5>' +
                '<div class="created">' + 
				    	'<p>Created by <strong><a id="author_popuplink" href="#author-popup" class="play-icon popup-with-zoom-anim f-history f-help">' + video.manifest_json.author.name + '</a></strong></p>' +
				'</div>' +
				    '<div class="clearfix"> </div>' +
				'</div>' +
        '</li>';
        $("#horizontal-style").append(videoElement);
        
        if ( ZopSpace.UPDATE_AUTHOR_CTRL )
            ZopSpace.populateAuthorListCtrl(video);
    },
    populateAuthorListCtrl: function( video ){
        //add author to dropdown list
        if (!$("#author_dropdown option[value='" + video.manifest_json.author.name + "']").length){
            $("#author_dropdown").append($("<option>",{value: video.manifest_json.author.name}).text(video.manifest_json.author.name));
        }
    },
    authorPopupEvent: function(){
       console.log("attaching popup event");
       /*
       if ( authorEvent < 2) {
          authorEvent += 1;
          setTimeout(ZopSpace.authorPopupEvent, 2000);
       }*/
       $("a.popup-with-zoom-anim").magnificPopup({
						type: 'inline',
						fixedContentPos: false,
						fixedBgPos: true,
						overflowY: 'auto',
						closeBtnInside: true,
						preloader: false,
						midClick: true,
						removalDelay: 300,
						mainClass: 'my-mfp-zoom-in'
					});
    }
};