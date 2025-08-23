using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Globalization;
using System.Linq;
using System.Security.Policy;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using static System.Windows.Forms.VisualStyles.VisualStyleElement.Tab;

namespace airportFixer
{
    public partial class askUpdateForm : Form
    {
        public string airportType { get; set; }
        public string name { get; set; }
        public string ICAO { get; set; }

        public string Pistes { get; set; }
        public string Longueurs { get; set; }
        public string Surfaces { get; set; }
        public string WIKIURL { get; set; }

        public string googleMapsURL { get; set; }

        public double latitude { get; set; }
        public double longitude { get; set; }

        private int mouseX;
        private int mouseY;

        private string TOKEN = "824471108d691ee8429b216e9e6bd72e3665127f896753b89b479636cec4457bb15a71cd32b2b7479c92a0c788a94043";

        private enum NEXTLINE
        {
            NONE,
            LENGTH,
            SURFACE
        };


        public askUpdateForm()
        {
            InitializeComponent();

            //initialize l'autocomplete for tbpistes with all runway names from 01 to 36 and 18/36
            var autoComplete = new AutoCompleteStringCollection();
            for (int i = 1; i <= 36; i++)
            {
                autoComplete.Add(i.ToString("D2") + "-" + (((i + 18) % 36 == 0 ? 36 : (i + 18) % 36).ToString("D2")));
            }
            autoComplete.Add("??-??");

            tbPistes.AutoCompleteCustomSource = autoComplete;
            tbPistes.AutoCompleteMode = AutoCompleteMode.SuggestAppend;
            tbPistes.AutoCompleteSource = AutoCompleteSource.CustomSource;

            //autocomplete for surfaces
            var autoCompleteSurfaces = new AutoCompleteStringCollection();
            autoCompleteSurfaces.AddRange(new string[] { "ASP", "CONC","GRAVEL", "GRASS", "TURF", "WATER", "ICE", "SNOW", "DIRT", "EARTH","UNK" });
            tbSurfaces.AutoCompleteCustomSource = autoCompleteSurfaces;
            tbSurfaces.AutoCompleteMode = AutoCompleteMode.SuggestAppend;
            tbSurfaces.AutoCompleteSource = AutoCompleteSource.CustomSource;

        }

        private void askUpdateForm_Load(object sender, EventArgs e)
        {
            this.Text = "Update " + name +"(" + ICAO +  ") : " + airportType;
            tbPistes.Text = Pistes;
            tbLongueurs.Text = Longueurs;
            tbSurfaces.Text = Surfaces;
            linkLabel1.Text = WIKIURL;
            linkLabel1.Links.Add(0, linkLabel1.Text.Length, WIKIURL);
            tbURLUpdate.Text = WIKIURL;

            googleMapsURL = "https://www.google.com/maps/place/";
            //convert latitude and longitude to DMS format and add it to the search query
            string latDMS = ConvertToDMS(latitude, true);
            string lonDMS = ConvertToDMS(longitude, false);
            googleMapsURL += "+" + latDMS + "+" + lonDMS;
            googleLinkLabel.Text = googleMapsURL;
            googleLinkLabel.Links.Add(0, googleLinkLabel.Text.Length, googleMapsURL);

            if (string.IsNullOrEmpty(WIKIURL))
            {
                linkLabel1.Visible = false;
                //WIKIURL = "http://www.google.com/search?q=" + name + " + wiki";
                WIKIURL = googleMapsURL;
            }
            else
            {
                linkLabel1.Visible = true;
            }
            try
            {
                //open the link in the default browser
                System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
                {
                    FileName = WIKIURL,
                    UseShellExecute = true
                });
            }
            catch (Exception ex)
            {
            }
        }

        private void button1_Click(object sender, EventArgs e)
        {
            Pistes = tbPistes.Text;
            Longueurs = tbLongueurs.Text;
            Surfaces = tbSurfaces.Text;
            WIKIURL = tbURLUpdate.Text;
            this.DialogResult = DialogResult.OK;
            this.Close();
        }

        private void linkLabel1_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            //open the link in the default browser
            System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
            {
                FileName = WIKIURL,
                UseShellExecute = true
            });
        }

        private void button2_Click(object sender, EventArgs e)
        {

        }

        private void askUpdateForm_MouseDown(object sender, MouseEventArgs e)
        {
            //memorize the mouse position
            mouseX = e.X;
            mouseY = e.Y;
        }

        private void askUpdateForm_MouseMove(object sender, MouseEventArgs e)
        {

        }

        private void button3_Click(object sender, EventArgs e)
        {
            RunwayFinder runwayFinder = new RunwayFinder();
            runwayFinder.runways = tbPistes.Text;
            if (runwayFinder.ShowDialog() == DialogResult.OK)
            {
                tbPistes.Text = runwayFinder.runways;
            }
        }

        private string ConvertToDMS(double value, bool isLatitude)
        {
            double absValue = Math.Abs(value);
            int degrees = (int)absValue;
            double minutesFull = (absValue - degrees) * 60.0;
            int minutes = (int)minutesFull;
            double seconds = (minutesFull - minutes) * 60.0;

            string direction;
            if (isLatitude)
                direction = value >= 0 ? "N" : "S";
            else
                direction = value >= 0 ? "E" : "W";

            // Utilise InvariantCulture pour forcer le séparateur '.'
            return $"{degrees}°{minutes}'{seconds.ToString("0.##", System.Globalization.CultureInfo.InvariantCulture)}{direction}";
        }

        private void googleLinkLabel_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            //open the link in the default browser
            System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
            {
                FileName = googleMapsURL,
                UseShellExecute = true
            });
        }

        private void button4_Click(object sender, EventArgs e)
        {
            //https://airportdb.io/api/v1/airport/{ICAO}?apiToken={apiToken}
            string requestUrl = $"https://airportdb.io/api/v1/airport/{ICAO}?apiToken={TOKEN}";
            try
            {
                using (var webClient = new System.Net.WebClient())
                {
                    string response = webClient.DownloadString(requestUrl);
                    //parse the json response
                    var json = System.Text.Json.JsonDocument.Parse(response);
                    if (json.RootElement.TryGetProperty("error", out var errorElement))
                    {
                        MessageBox.Show("Error from API: " + errorElement.GetString());
                        return;
                    }

                    // Extraction des champs principaux
                    var root = json.RootElement;
                    if (root.TryGetProperty("type", out var typeElem))
                        airportType = typeElem.GetString();
                    if (root.TryGetProperty("name", out var nameElem))
                        name = nameElem.GetString();
                    if (root.TryGetProperty("ident", out var identElem))
                        ICAO = identElem.GetString();
                    if (root.TryGetProperty("wikipedia_link", out var wikiElem))
                        WIKIURL = wikiElem.GetString();
                    if (root.TryGetProperty("latitude_deg", out var latElem))
                        latitude = latElem.GetDouble();
                    if (root.TryGetProperty("longitude_deg", out var lonElem))
                        longitude = lonElem.GetDouble();

                    // Extraction des pistes (runways)
                    if (root.TryGetProperty("runways", out var runwaysElem) && runwaysElem.ValueKind == System.Text.Json.JsonValueKind.Array)
                    {
                        var pistesList = new List<string>();
                        var longueursList = new List<string>();
                        var surfacesList = new List<string>();

                        foreach (var runway in runwaysElem.EnumerateArray())
                        {
                            // Noms des pistes (ex: "04L-22R")
                            string le = runway.TryGetProperty("le_ident", out var leElem) ? leElem.GetString() : "";
                            string he = runway.TryGetProperty("he_ident", out var heElem) ? heElem.GetString() : "";
                            if (!string.IsNullOrEmpty(le) && !string.IsNullOrEmpty(he))
                                pistesList.Add($"{le}-{he}");

                            // Longueur (en pieds)
                            if (runway.TryGetProperty("length_ft", out var lenElem))
                                longueursList.Add(lenElem.GetString());

                            // Surface
                            if (runway.TryGetProperty("surface", out var surfElem))
                                surfacesList.Add(surfElem.GetString());
                        }

                        Pistes = string.Join(" ", pistesList);
                        Longueurs = string.Join("/", longueursList);
                        Surfaces = string.Join("/", surfacesList);
                    }

                    // Met à jour les champs de l'UI
                    tbPistes.Text = Pistes;
                    tbLongueurs.Text = Longueurs;
                    tbSurfaces.Text = Surfaces;
                    tbURLUpdate.Text = WIKIURL;
                    linkLabel1.Text = WIKIURL;
                    linkLabel1.Links.Clear();
                    linkLabel1.Links.Add(0, WIKIURL.Length, WIKIURL);

                    // Met à jour la position et le lien Google Maps
                    string latDMS = ConvertToDMS(latitude, true);
                    string lonDMS = ConvertToDMS(longitude, false);
                    googleMapsURL = "https://www.google.com/maps/place/+" + latDMS + "+" + lonDMS;
                    googleLinkLabel.Text = googleMapsURL;
                    googleLinkLabel.Links.Clear();
                    googleLinkLabel.Links.Add(0, googleMapsURL.Length, googleMapsURL);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error fetching data from API: " + ex.Message);

            }
        }
    }
}
