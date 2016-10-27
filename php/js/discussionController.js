var DiscussionController = {
    
    loadDiscussion: function (vid_id) {
        data = {video_id:vid_id};
        $.ajax({
            url: "comment_action.php",
            data: data,
            dataType: "json",
            success: function(response) {
                if ( response.status == 'success') {
                    //console.log(response.data);
                    if ( response.process )
                        DiscussionController.populateDiscView(response.data);
                    else
                        $(".media-grids").html("No comments available");
                } else if ( response.status == 'error') {
                    $.notify(response.reason, "error");
                }
            },
            error: function(xhr, code, reason) {
                console.log(code + ":" + reason);
            }
        });
    },
    populateDiscView: function (disc_data) {
        //set comments count
        $("#comments_count").val("All Comments ("+disc_data.length+")");
        $(".media-grids").html("");
        disc_data.forEach(function(comment){
           var discComment = '<div class="media">'
                +  '<h5>' + comment.user_name + '</h5>'
			     + '<div class="media-left">'
				 +     '<a href="#"></a>'
			     + '</div>'
			     + '<div class="media-body">'
				 +     '<p>' + comment.comment + '</p>'
			     + '</div>'
		      + '</div>';
            $(".media-grids").append(discComment);
        });
    },
    submitComment: function () {
        var formObj = $("#disc_form");
        var data = formObj.serializeArray();
        console.log(data);
        $.ajax({
            url: formObj.data("url"),
            data: data,
            dataType: "json",
            success: function(response) {
                if ( response.status == 'success') {
                    $.notify("Comment has been added successfully.", "success");
                    //now re-load the discussion panel
                    console.log(data);
                    var vidid = data[1].value;
                    console.log("refreshing discussion view for video "+ vidid);
                    DiscussionController.loadDiscussion(vidid);
                } else if ( response.status == 'error') {
                    $.notify(response.reason, "error");
                }
            },
            error: function(xhr, code, reason) {
                console.log(code + ":" + reason);
            }
        });
        
        return false;
    }
};