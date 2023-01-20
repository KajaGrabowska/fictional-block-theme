import $ from "jquery";

class Like {
  constructor() {
    this.events();
  }

  events() {
    $(".like-box").on("click", this.ourClickDispatcher.bind(this));
  }

  // methods
  ourClickDispatcher(event) {
    var currentLikeBox = $(event.target).closest(".like-box"); //closets looks for the closest parent element that matches the class like-box

    if (currentLikeBox.attr("data-exists") == "yes") {
      this.deleteLike(currentLikeBox);
    } else {
      this.createLike(currentLikeBox);
    }
  }

  createLike(currentLikeBox) {
    $.ajax({
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", universityData.nonce);
      },
      url: universityData.root_url + "/wp-json/university/v1/manageLike",
      type: "POST",
      data: {'professorId': currentLikeBox.data('professor')},
      success: (response) => {
        currentLikeBox.attr('data-exists', 'yes'); //this line will update the heart to be filled in
        var likeCount = parseInt(currentLikeBox.find(".like-count").html(), 10);
        likeCount++; //increments the likecount by 1
        currentLikeBox.find(".like-count").html(likeCount); //updates the like count on the front-end
        currentLikeBox.attr("data-like", response);
        console.log(response);
      },
      error: (response) => {
        console.log(response);
      },
    });
  }

  deleteLike(currentLikeBox) {
    $.ajax({
      beforeSend: (xhr) => {
        xhr.setRequestHeader("X-WP-Nonce", universityData.nonce);
      },
      url: universityData.root_url + "/wp-json/university/v1/manageLike",
      data: {'like': currentLikeBox.attr('data-like')},
      type: "DELETE",
      success: (response) => {
        currentLikeBox.attr('data-exists', 'no'); //this line will update the heart to be empty
        var likeCount = parseInt(currentLikeBox.find(".like-count").html(), 10);
        likeCount--; //decreases the likecount by 1
        currentLikeBox.find(".like-count").html(likeCount); //updates the like count on the front-end
        currentLikeBox.attr("data-like", '');
        console.log(response);
      },
      error: (response) => {
        console.log(response);
      },
    });
  }
}

export default Like;
