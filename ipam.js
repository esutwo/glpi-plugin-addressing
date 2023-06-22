// Compute and Check the input data
function plugipam_Compute(msg) {

   var ipdeb = new Array();
   var ipfin = new Array();
   var subnet = new Array();
   var netmask = new Array();
   var i;
   var val;

   document.getElementById("plugipam_range").innerHTML = "";
   document.getElementById("plugipam_ipdeb").value = "";
   document.getElementById("plugipam_ipfin").value = "";

   for (var i = 0; i < 4; i++) {
      val=document.getElementById("plugipam_ipdeb"+i).value;
      if (val=='' || isNaN(val) || parseInt(val)<0 || parseInt(val)>255) {
         document.getElementById("plugipam_range").innerHTML=msg+" ("+val+")";
         return false;
      }
      ipdeb[i]=parseInt(val);

      val=document.getElementById("plugipam_ipfin"+i).value;
      if (val=='' || isNaN(val) || parseInt(val)<0 || parseInt(val)>255) {
         document.getElementById("plugipam_range").innerHTML=msg+" ("+val+")";
         return false;
      }
      ipfin[i]=parseInt(val);
   }

   if (ipdeb[0]>ipfin[0]) {
      document.getElementById("plugipam_range").innerHTML=msg+" ("+ipdeb[0]+">"+ipfin[0]+")";
      return false;
   }
   if (ipdeb[0]==ipfin[0] && ipdeb[1]>ipfin[1]) {
      document.getElementById("plugipam_range").innerHTML=msg+" ("+ipdeb[1]+">"+ipfin[1]+")";
      return false;
   }
   if (ipdeb[0]==ipfin[0] && ipdeb[1]==ipfin[1] && ipdeb[2]>ipfin[2]) {
      document.getElementById("plugipam_range").innerHTML=msg+" ("+ipdeb[2]+">"+ipfin[2]+")";
      return false;
   }
   if (ipdeb[0]==ipfin[0] && ipdeb[1]==ipfin[1] && ipdeb[2]==ipfin[2] && ipdeb[3]>ipfin[3]) {
      document.getElementById("plugipam_range").innerHTML=msg+" ("+ipdeb[3]+">"+ipfin[3]+")";
      return false;
   }
   document.getElementById("plugipam_range").innerHTML=""+ipdeb[0]+"."+ipdeb[1]+"."+ipdeb[2]+"."+ipdeb[3]+" - "+ipfin[0]+"."+ipfin[1]+"."+ipfin[2]+"."+ipfin[3];
   document.getElementById("plugipam_ipdeb").value=""+ipdeb[0]+"."+ipdeb[1]+"."+ipdeb[2]+"."+ipdeb[3];
   document.getElementById("plugipam_ipfin").value=""+ipfin[0]+"."+ipfin[1]+"."+ipfin[2]+"."+ipfin[3];
   return true;
}

// Check the input data (from onSubmit)
function plugipam_Check(msg) {

   if (plugipam_Compute(msg)) {
      return true;
   }
   alert(msg);
   return false;
}

// Display initial values
function plugipam_Init(msg) {

   var ipdeb = new Array();
   var ipfin = new Array();

   var ipdebstr = document.getElementById("plugipam_ipdeb").value;
   var ipfinstr = document.getElementById("plugipam_ipfin").value;

   document.getElementById("plugipam_range").innerHTML=""+ipdebstr+" - "+ipfinstr;

   ipdeb=ipdebstr.split(".");
   ipfin=ipfinstr.split(".");
   for (i=0;i<4;i++) {
      document.getElementById("plugipam_ipdeb"+i).value=ipdeb[i];
      document.getElementById("plugipam_ipfin"+i).value=ipfin[i];
   }
}

// Refresh the check message after onChange (from text input)
// function plugipam_ChangeNumber(msg) {
//
//    var lst=document.getElementById("plugipam_subnet");
//    if (lst!=null) {
//        lst.selectedIndex=0;
//    }
//
//    plugipam_Compute(msg);
// }

// Refresh the check message after onChange (from list)
// function plugipam_ChangeList(id,msg) {
//
//    var i;
//    var lst=document.getElementById(id);
//    if (lst.value == "0") {
//       return;
//    }
//    var champ=lst.value.split("/");
//    var subnet=champ[0].split(".");
//    var netmask=champ[1].split(".", 4);
//    var ipdeb = new Array();
//    var ipfin = new Array();
//
//    netmask[3] = parseInt(netmask[3]).toString();
//    if (lst.selectedIndex>0) {
//       for (var i=0;i<4;i++) {
//          ipdeb[i]=subnet[i]&netmask[i];
//          ipfin[i]=ipdeb[i]|(255-netmask[i]);
//          if (i==3) {
//             ipdeb[3]++;
//             ipfin[3]--;
//          }
//          document.getElementById("plugipam_ipdeb"+i).value=ipdeb[i];
//          document.getElementById("plugipam_ipfin"+i).value=ipfin[i];
//       }
//       plugipam_Compute(msg);
//    }
// }

function nameIsThere(params) {
    var root_doc = params;
    var nameElm = $('input[id*="name_reserveip"]');
    var typeElm = $('select[name="type"]');
    var divNameItemElm = $('div[id="nameItem"]');
    $.ajax({
         url: root_doc + '/front/ipam.php',
         type: "GET",
         dataType: "json",
         data: {
            action: 'isName',
            name: (nameElm.length != 0) ? nameElm.val() : '0',
            type: (typeElm.length != 0) ? typeElm.val() : '0',
         },
         success: function (json) {
            if (json) {
                divNameItemElm.show();
            } else {
               divNameItemElm.hide();
            }
         }
      });
}
