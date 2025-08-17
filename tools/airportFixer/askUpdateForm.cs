using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace airportFixer
{
    public partial class askUpdateForm : Form
    {
        public string name { get; set; }
        public string Pistes { get; set; }
        public string Longueurs { get; set; }
        public string Surfaces { get; set; }
        public string WIKIURL { get; set; }

        public askUpdateForm()
        {
            InitializeComponent();
        }

        private void askUpdateForm_Load(object sender, EventArgs e)
        {
            this.Text = "Update " + name;
            tbPistes.Text = Pistes;
            tbLongueurs.Text = Longueurs;
            tbSurfaces.Text = Surfaces;
            linkLabel1.Text = WIKIURL;
            linkLabel1.Links.Add(0, linkLabel1.Text.Length, WIKIURL);
            tbURLUpdate.Text = WIKIURL;

            if (string.IsNullOrEmpty(WIKIURL))
            {
                linkLabel1.Visible = false;
            }
            else
            {
                linkLabel1.Visible = true;
                //open the link in the default browser
                System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
                {
                    FileName = WIKIURL,
                    UseShellExecute = true
                });
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
    }
}
