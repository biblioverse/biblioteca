import { decode } from "blurhash";

function createDiv(className) {
  const div = document.createElement("div");
  div.className = className;

  return div;
}

function measureSizes(container, columnCount) {
    let containerHeight = container.parentNode.getBoundingClientRect().height;
    let containerWidth = container.parentNode.getBoundingClientRect().width;

    // Account for the skew and scaling ratio
    const columnWidth = (containerWidth / columnCount) * 1.5;
    const itemHeight = columnWidth * 0.75;

    return {
        containerHeight,
        itemHeight,
    }
}

function addImage(columnContent) {
    const pixels =
      DECODED_IMAGES[Math.floor(Math.random() * DECODED_IMAGES.length)];

    const item = createDiv("LoginMasonry__item");
    columnContent.appendChild(item);
    const canvas = document.createElement("canvas");
    canvas.width = 14;
    canvas.height = 20;
    item.appendChild(canvas);

    const ctx = canvas.getContext("2d");
    const imageData = ctx.createImageData(14, 20);
    imageData.data.set(pixels);
    ctx.putImageData(imageData, 0, 0);
}

const POSSIBLE_IMAGES = [
  // Asterix le gaulois
  "|oL4[?kRX,bIkVjbkWsAkD~Xogt9jFn-j?t7SLt6n$ogxvjZV@WUjYWVj@xXWAXObbVra$jFoeW;bcj?WBjEWEkBR*WWe.t9WEWnWBf+o1WCoffis=a#axjuj?kDkCoeays:axWAjujuofa}j[aea$RjW;fij?n%n*kCWW",
  // Lanfeust de troy
  "|MEDCv%04;5ZM{wbnNs8s7p{%2D*ENRjxCxCjZWBq[S$warWSixttQS$R.E2M{xsskWBkCozs.R-kDozoeSiR*WBjujEs.xtxCRkSiofWBR.oJj=XTWBaKa#s:odWVWCbIOuofr=R*s,n$sRjZWYSjs:adR*WBs-s8oeR-",
  // Watchmen
  "|ZDTwx0[?QDkWC%KIYt1of%FNMtPRjayofWCoekCRpt3WEozayWBoffRV[s%RntQWBadj?WrbHaeRls+WCoyWBWBofWYj?ngV@oeazj[jYaeoea#i]V?oef,jrWAaejYafjZayayj@j]j?WBj@a#n}RjoIayjsofWYj?a~",
  // Dragon ball
  "|fLz~rXS%Mob%1t7x[og%LIwRiodt7oeR%j?jZbF.Tn,smRjNGkCoLj=WCInWXR*fkoMf6ogkCae%3bEM|jYoyWXf6a#jZIVxaxaWoa#WBaxj?j?WAWBt7ahoMjrWBj[kBxDWCt6WAR%offka}a$%1aeRiozafj[ofjuay",
  // One piece
  "|gMjjqxZ%gS5V[n$xut7t7_4NHjYxat7ayt7aeofIUs,WBWXtRbwaeays:X9R*s:Rjt7s:V@R+bHRkaxademoKV?fkofj[n%aet7R+WXofofj]jaogShW=t8e.WBWBf5ofxtt6WVbIa{aejZaebIt7jsn$axWCkDayWCWB",
  // V for vendetta
  "|JIFoA?bIURj8wRkM_t7WBDOX9xuw|.SjFt7s:bHMxVsoyofIUozx]ayRjQ-DiS1a{WoWBRPt7t7R6IADiNGWBafozWBV@4ntRx[aet7fkRjWBjZI:x]i{WBR%ofxuaet7?^tRWBaeoLazMxR*RP8_t7xuj]xuoekCWBf6",
  // uncle scrooge
  "|WJ8Ryni.8nNx[f+oft6ozyZaLSjWBbcWBV[oeWYEVxVxbofRjWBWVWYWBESWBt6W?RPbIaxR.ocJARPROoff5buRjoIf5.8aKRPaxR*f+jYoeWBx]X9nhR%oLoLogoIj[kXt7V@WBocNHkVaet6IpW?WTkDM|WVflWCt7",
  // fullmeta alchemist
  "|EFhu2BW0L-O0fMyo|t7M{x[R+WWw^RQX8RjNGxu0M+ZwHE,xCtRIBofoz?wx[DiI=s+nOozs-V@x[xuo~rqMxkCa_R*WBo_56V@?abJRjjFsAt8%No}v}M{W;WBa#j[ShEi-Ux]IpVsofoJNwjFa0V@Rjtlt7M{xajFs:",
  // killing joke
  "|SCZkb%M-qxuxut7xut7t7~XoytRM{RiRjWBWBWB?uxu-qbFj]t6t7t7ofs;WBbFRjWBWBWBj]j[t7RjRjofoMayayWBj[ofWBWBofofozj[ayofjvWBj[j[j]juayWBayt7ayofofofbFayWBayoMbFofj[j[WBayWBay",
  // Naruto
  "|fM?#kRj%$WB.8t6%MoztRE0R%bIV[s:jYs:ofoM_Naxo0W;n+oJaykCWCxCt6WBkDWBoJW=flkDX-kBnOj]RjayRjofWB$jaeWUbFW=WCofoJt6-oW.NHn*Rja#o2n*f8x]WBWBoeoIbIaxayWVbFjZofkBofaykCWXfk",
  // attack on titan
  "|JKJ*~9b-ov}IotRRjRjIoDPRPsBs:kCMxt7xus:uiIAD%ELX8xaaeNGs:GGtlyDtRRPWBM{bHWBE2x[E1oJ%Ma#t7WUkCMxn$nOsAM{SgWVs:R+R5n%-oR+bHt6RkRjofwJx]aeWAozs:t7t7WBD%ayNGxaM|o2aKWooz",
  // persepolis
  "|SPo[C3XD%Q-E1IUae%M%2|G:+tRTJs:xaxGWBRj+u#StR%2$jXSI:Mxa0yrxtq]RjOXe.r?ozo1I:Nvt7s:a0R*ofofj[sBS~OYV@i_aeWBV@bbV@NGIoS#xuxZofbbayn4ofxaoeNaNGWBjFj[iws9snjFS~kCofayWV",
  // de cape et de crocs
  "|C8i9lOAs*s8NMozV@axozK-v}ogf,V?kCj]a$WBHqo}M|WXxWafRkozWC${Xnt7aeS5V?oeaxWEoLV[a#a_bca_ozayn$NfobV?aeadbJaeofocW?s.ofa~jsa#WUayogjZRkt7oej]ofkCayofMyWBWAn$axofofflof",
  // tintin au tibet
  "|XPZWAH?u4v~#mv}vgn4ic8{ozTxaeVrj[i_s:ozAHxuV@V@oJayofayRjGaRPiIxut7fkXSWBs:uixarDWBM_RjXSofozXnsoa0NFxaofkWWBWBIoM_s:ofWpogs:s:t7WAof$*jsb^WAV@ayV@tRs:r=f5S#WVV[WBae",
  // xiii
  "|9G*~4-:00DhMxIrkEoefi8w%KtmR:ofIVj=xuR-05D$-m?H~9%19bt7t7t1IwNMM^t7WENGsRob?HMxE2.7xaozoIRPoe00R%%ht8t7skjbbHkC=^-no#D*ITW?%MbJM{%g?bMxDjM|ofozNGoz9Fozx]tQn#s,M}Rkoc",
  // scott pilgrim
  "|YN[A;X*}9eow[-irrWYV_^axUESoxoJNejaoes,${WEN0t4ofoLjba_a{a2bbrskVN_n$kVW.R+VxS6skxUSdRkoct5tQVxnPW=I]xWt5NwjGaevmadR-nlW;bbjYR+fiVwS1oxsSbXWYodofkBogkDj[R+X7jYj=jaa|",
  // blake & mortimer
  "|UHxNJNGD,NHIqNGn,t6t4~WS4kDoeoLt6flWCoKcGWCxss9t3s.t5WERlIbs.xYbHkANIoKt6WCnzR*oeRkV[j]Rlj?WVI9R-R*oIWBoeRkWXodI9RjRkR*WWkBocj[ocDibYWBWBWCoLoJa|oeIUWBWCbFt5azWCj[Rl",
  // sandman
  "|eE.z[~p%Mt7bGt7xuxut7%2s:j[j]j[ofWBWBWBShbIt7ofjuWBRjWBWBoyofaekBofj@ofofWVadjZayWCj[j[ayWBoLWBj[ayayayjtj[a{oLWCofoeofj[ayj[fRazRjofofWBj@j[a{WBWUWBoefRayf6ayofofof",
  //thorgal
  "|dLChl,.?G$MxrofoJoyt6~CMyS#M|WBR+RjWqWVGGbboIXRWBofa_ofjbAZXSv}t7V@s:fRj@jbJ8kqnit7n$a{bHV[kCsloLWBRkoLV[j]n%kCwdaekCRjkCjFj[oeWpjGR*tQs.oyofoJX8j?r?V@ozoebIkVf6kCo0",
  // blacksad
  "|7A0%V9v0L~B9vofV[Ipxt0isnxZI;%0t6oes:bIIVxt%1IpofNGWBxaR-XhoLs;tQRQRjoMWCj[D+jFt6NI%0xaR*jst7^$NGIqxZM|ofWEWCoJV_R*oet6WBWBxtWBRjIVoet6WCayWCj[oKjsn~j[j[ayf6WVafj[WC",
  // bleach
  "|nN0-stR-;kCozM_x]V@t7~qt7S2ofkVayayjuWBD*M_WAWBtRkDjEbHV@Ipt8s:aeW=t7V@ofjZoLRkxuf5WBaxWBaejsxakCR+a}aKoeofaxofWBjYWCf7jZkCWBj[aytQV@n%t7ogWCoeWVog%Lt7RiRjWCayWVj[WB",
  // largo winch
  "|VPQ1@.8~qMwR*S3MxkCWB?uIUIUWBofofRjayWB9ZV?n$%gaeM{ozRjjZV@kC%MRPRjWBWBt7ayxuW.kDMxoftRV@ayR*%fjaRPtRofV@RjayxuxuRixuWBM{oLoft7WBMxt7RjoLxuWBofRjRjoMWBWBofRjayj[j?of",
  // tintin temple du soleil
  "|YH_C]$6Dj#mICwJrv,@#m~9R*WXayIqs:R*bHR-rpj?t7WEWrafWUj?R+IVWXt6s,t7WqWBWCn%ESs.R%R+w]oLxsj[j[9}WBn$oysmf5s:jboeMybIofjYoyj]WBbFs:M|oet6WVaybHofayafNHayofays.j@jFoLbH",
];

const DECODED_IMAGES = POSSIBLE_IMAGES.map((hash) => decode(hash, 14, 20));

const COLUMN_COUNT = 10;
const columns = [];

// We get the size of the container element
// This element is not skewed, so we can use it to get the size of the columns
const container = document.getElementById("login_masonry");
let sizes = measureSizes(container, COLUMN_COUNT);

for (let i = 0; i < COLUMN_COUNT; i++) {
  const column = createDiv("LoginMasonry__column");
  const columnContent = createDiv("LoginMasonry__column__content");
  column.appendChild(columnContent);
  container.appendChild(column);

  // Alternate column direction
  const direction = i % 2 === 0 ? "up" : "down";

  // Randomize speed slightly for each column
  const speed = 0.2 + Math.random() * 0.3;

  // Generate 8-12 images per column
  const imageCount = 8 + Math.floor(Math.random() * 5);
  for (let j = 0; j < imageCount; j++) {
    addImage(columnContent);
  }

  // We get the size of the column by calculating it instead of measuring it.
  // This is because the column is skewed, so we can't use getBoundingClientRect
  // to get the size of the column.
  const height = imageCount * sizes.itemHeight;

  // Initialize columns going down by setting them to their top offset
  let offset = 0;
  if (direction === "down") {
    offset = (height - sizes.containerHeight) * -1;
    columnContent.style.transform = `translateY(${offset}px)`;
  } else {
    columnContent.style.transform = `translateY(0)`;
  }

  columns.push({
    imageCount,
    speed,
    ref: columnContent,
    direction,
    height,
    offset,
  });
}

// Setup animation loop
let animationFrameId;
let lastTime = performance.now();

function animate(time) {
  // animation frames are not triggered when the tab is not visible
  // Setting a max of 2000 avoids a big jump when the tab is visible again
  const deltaTime = Math.min(time - lastTime, 2000);
  lastTime = time;

  for (const column of columns) {
    const currentOffset = column.offset;
    const currentDirection = column.direction;
    const movement = column.speed * (deltaTime / 16); // Normalize to ~60fps

    // Move in the appropriate direction
    const offset =
      currentDirection === "up"
        ? currentOffset - movement
        : currentOffset + movement;

    // Reverse direction when column has moved its full height
    if (offset >= 0) {
      column.direction = "up";
    } else if (offset <= (column.height - sizes.containerHeight) * -1) {
      column.direction = "down";
    }

    column.offset = offset;
    column.ref.style.transform = `translateY(${offset}px)`;
  }

  animationFrameId = requestAnimationFrame(animate);
};

animationFrameId = requestAnimationFrame(animate);

// Calculate the size of the container and columns
window.addEventListener("resize", () => {
    // Recalculate sizes
    sizes = measureSizes(container, COLUMN_COUNT);
    
    // Update column heights and offsets
    for (const column of columns) {
        column.height = column.imageCount * sizes.itemHeight;
    }
    
    // Restart the animation loop
    animationFrameId = requestAnimationFrame(animate);
});
