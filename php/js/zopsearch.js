var ZopSearch = {
    editForm: function() {
      $("#search-panel").toggle();
      $('#tag_input').val(''); //set to default as there are multiple values
    },
    
    loadAuthorList: function(lastAuthor){
    var videosUri = "http://zop.space/sc/php/authlist.php";        
    $.ajax({
            url:videosUri, 
            dataType: "json",
            success: function(authors){
                //console.log(response);
                if ( authors ){
                    console.log("Populating authors list");    
                    authors.forEach(function(author){
                        $("#author_dropdown").append($("<option>",{value: author}).text(author));
                    });
                    //now update the authorList control on search panel
                    var authorList = $("#author_dropdown option");  
                    authorList.sort(function(obj1, obj2){
                        var t1 = obj1.value.toLowerCase(), t2 = obj2.value.toLowerCase();
                        return t1 > t2 ? 1 : t1 < t2 ? -1 : 0;
                    });
                        
                    $("#author_dropdown").html(authorList);
                    $("#author_dropdown").val(lastAuthor);
                }
            },
            error: function(xhr, code, reason){
                console.log(code+":"+reason);
            }
        });
    },
    submit: function(){
        var formT = $("#search_form");
        var formdata = formT.serialize();
        console.log(formdata);
        formT.submit();
    }
};