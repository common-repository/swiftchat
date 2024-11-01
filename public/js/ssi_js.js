(function() {
  var pluginPrefix = "ssi";
  var apiURL = "https://api.swiftchat.io";

  var getAPIURL = function(action) {
    return apiURL + "/" + action;
  };

  jQuery(document).ready(function() {
    jQuery("#" + pluginPrefix + "_login_form").on("submit", function(e) {
      e.preventDefault();
      var formdata = {
        Email: jQuery("#" + pluginPrefix + "_email_input").val(),
        Password: jQuery("#" + pluginPrefix + "_password_input").val()
      };

      //TODO check values

      jQuery.ajax({
        url: getAPIURL("login"),
        data: formdata,
        crossDomain: true,
        dataType: "json",
        type: "POST",
        success: function(response) {
          var accessToken = response.Token;
          if (!accessToken) {
            alert("Invalid login!");
            return;
          }
          jQuery.ajax({
            url: getAPIURL("me"),
            type: "GET",
            headers: { Authorization: accessToken },
            success: function(response) {
              var memberships = response.AccountMemberships;
              if (!memberships || !memberships.length) {
                alert("Account have no memberships");
                return;
              }
              var adminMembership = memberships.filter(function(membership) {
                return membership.Type == 1 && membership.Status == 1;
              })[0];

              if (!adminMembership) {
                alert("No Admin memberships found ");
                return;
              }
              var accountId = adminMembership.AccountId;
              jQuery.ajax({
                url: "admin-ajax.php",
                data: {
                  action: pluginPrefix + "_login",
                  token: accessToken,
                  account_id: accountId
                },
                type: "POST",
                success: function(response) {
                  jQuery("#" + pluginPrefix + "_connect_account_view").hide();
                  location.reload(true);
                },
                error: function() {
                  alert("Error : Something went wrong.");
                }
              });
            },
            error: function() {
              alert("Error : Something went wrong.");
            }
          });
        },
        error: function() {
          alert("Error : Unauthorized for request.");
        }
      });
    });

    jQuery("#" + pluginPrefix + "_disconnect_btn").on("click", function(e) {
      e.preventDefault();

      jQuery.ajax({
        url: "admin-ajax.php",
        data: {
          action: pluginPrefix + "_logout",
          remove_website_id: true
        },
        type: "POST",
        success: function(response) {
          location.reload(true);
        }
      });
    });

    var dataDomObject = jQuery("#" + pluginPrefix + "_data_attr");
    var websiteId = dataDomObject.data(pluginPrefix + "_website_id");
    var authToken = dataDomObject.data(pluginPrefix + "_token");
    var accountId = dataDomObject.data(pluginPrefix + "_account_id");

    if (authToken && accountId) {
      jQuery.ajax({
        url: getAPIURL("websites"),
        type: "GET",
        headers: {
          Authorization: authToken,
          "X-ACCOUNT-ID": accountId
        },
        success: function(response) {
          var websites = response.Websites;
          if (!websites.length) {
            return alert("No websites in this account");
          }

          jQuery.each(websites, function(i, website) {
            jQuery("#" + pluginPrefix + "_website_select").append(
              '<option value="' + website.Id + '">' + website.Name + "</option>"
            );
          });
          jQuery("#" + pluginPrefix + "_website_select").val(websiteId);

          jQuery("#" + pluginPrefix + "_website_select").on("change", function(
            e
          ) {
            var selectedWebsiteId = jQuery(
              "#" + pluginPrefix + "_website_select"
            ).val();

            var selectedWebsite = websites.filter(function(website) {
              return selectedWebsiteId == website.Id;
            })[0];

            if (selectedWebsite === undefined) {
              return alert("invalid website");
            }

            if (
              selectedWebsite.Domain.indexOf(window.location.hostname) === -1
            ) {
              jQuery("#" + pluginPrefix + "_alertline").show();
            } else {
              jQuery("#" + pluginPrefix + "_alertline").hide();
            }
          });

          jQuery("#" + pluginPrefix + "_website_select_form").on(
            "submit",
            function(e) {
              e.preventDefault();
              var selectedWebsiteId = jQuery(
                "#" + pluginPrefix + "_website_select"
              ).val();
              jQuery.ajax({
                url: "admin-ajax.php",
                data: {
                  action: pluginPrefix + "_post_id",
                  id: selectedWebsiteId
                },
                type: "POST",
                success: function(response) {
                  window.location.reload();
                }
              });
            }
          );
        }
      });
    }
  });
})();
