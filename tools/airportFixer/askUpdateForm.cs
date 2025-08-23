using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
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
        public string Pistes { get; set; }
        public string Longueurs { get; set; }
        public string Surfaces { get; set; }
        public string WIKIURL { get; set; }

        public string googleMapsURL { get; set; }

        public double latitude { get; set; }
        public double longitude { get; set; }

        private int mouseX;
        private int mouseY;

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
                autoComplete.Add(i.ToString("D2"));
                autoComplete.Add(i.ToString("D2") + "-" + (((i + 18) % 36 == 0 ? 36 : (i + 18) % 36).ToString("D2")));
            }
            autoComplete.Add("??-??");

            tbPistes.AutoCompleteCustomSource = autoComplete;
            tbPistes.AutoCompleteMode = AutoCompleteMode.SuggestAppend;
            tbPistes.AutoCompleteSource = AutoCompleteSource.CustomSource;
        }

        private void askUpdateForm_Load(object sender, EventArgs e)
        {
            this.Text = "Update " + name + " : " + airportType;
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
    }
}
