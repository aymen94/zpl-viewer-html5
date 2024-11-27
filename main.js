function buildCanvas(data) {
  var layer = new Konva.Layer();

  stage.destroyChildren();

  var current_y_position = 10; 

  try {
    var executeData = new Function("layer", "current_y_position", data);
    executeData(layer, current_y_position);
  } catch (e) {
    console.error("Error executing data:", e);
    return;
  }

  var rect = new Konva.Rect({
    x: 10,
    y: 10,
    width: 900,
    height: 1000,
    stroke: "black",
    strokeWidth: 1,
  });
  layer.add(rect);

  // Add the layer to the stage
  stage.add(layer);
}

function callZebra() {
  var base64TxtZebra = document.getElementById("base64TxtZebra");
  var txtZebra = document.getElementById("txtZebra");
  var data;

  if (base64TxtZebra.value.trim() === "") {
    var data = txtZebra.value;
  } else {
    var decodedData = decodeBase64(base64TxtZebra.value);
    txtZebra.value = decodedData;
    data = decodedData;
  }

  fetch("index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({ zpl: data }),
  })
    .then((response) => response.text())
    .then(buildCanvas)
    .catch((error) => {
      console.error("Error:", error);
    });
}

function decodeBase64(base64) {
  return atob(base64);
}

function desenhaLinhaRegua(x1, y1, x2, y2, c, d) {
  return new Konva.Line({
    points: [x1, y1, x2, y2],
    stroke: c,
    strokeWidth: 1,
    dash: [1, d],
  });
}

// Ensure roda_0 is defined
var roda_0 = {}; // Define roda_0 appropriately based on your requirements

var stage = new Konva.Stage({
  container: "container",
  width: 600, // Set fixed width
  height: 1000, // Set fixed height
});

stage.setName("stgEtiqueta");

// Remove the window resize event listener
// window.addEventListener("resize", function () {
//   stage.width(window.innerWidth);
//   stage.height(window.innerHeight);
// });

var layer = new Konva.Layer();

var text = new Konva.Text({
  x: stage.width() / 4,
  y: stage.height() / 6,
  text: "Hello, User!",
  fontSize: 20,
  fontFamily: "Calibri",
  fill: "black",
  align: "center",
  verticalAlign: "middle",
});

// Center the text
text.offsetX(text.width() / 2);
text.offsetY(text.height() / 2);

layer.add(text);
stage.add(layer);

// Update text position on window resize
// window.addEventListener("resize", function () {
//   text.x(stage.width() / 2);
//   text.y(stage.height() / 2);
//   text.offsetX(text.width() / 2);
//   text.offsetY(text.height() / 2);
//   layer.batchDraw();
// });

// Add scrollbars to the container
var container = document.getElementById("container");
container.style.overflow = "auto";
container.style.width = "800px"; // Set fixed width
container.style.height = "600px"; // Set fixed height

// Add download buttons functionality
document.getElementById('downloadImage').addEventListener('click', function() {
  var dataURL = stage.toDataURL({ pixelRatio: 3 });
  var link = document.createElement('a');
  link.download = 'canvas_image.png';
  link.href = dataURL;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
});

document.getElementById('downloadPDF').addEventListener('click', function() {
  var dataURL = stage.toDataURL({ pixelRatio: 3 });
  const { jsPDF } = window.jspdf;
  var pdf = new jsPDF();
  pdf.addImage(dataURL, 'PNG', 0, 0, 210, 297); // A4 size: 210x297 mm
  pdf.save('canvas.pdf');
});