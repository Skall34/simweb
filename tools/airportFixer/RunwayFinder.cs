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
    public partial class RunwayFinder : Form
    {
        public string runways { get; set; }

        // Ajoutez ces champs à la classe askUpdateForm
        private Point? startPoint = null;
        private Point? currentPoint = null;
        private bool isDrawing = false;


        public RunwayFinder()
        {
            InitializeComponent();
        }

        private void RunwayFinder_Load(object sender, EventArgs e)
        {

        }

        // Gestion du MouseDown
        private void panel1_MouseDown(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                startPoint = e.Location;
                currentPoint = e.Location;
                isDrawing = true;
                this.Invalidate();
            }
        }

        // Gestion du MouseMove
        private void panel1_MouseMove(object sender, MouseEventArgs e)
        {
            if (isDrawing && e.Button == MouseButtons.Left)
            {
                currentPoint = e.Location;
                this.Invalidate();
            }
        }

        // Gestion du MouseUp
        private void panel1_MouseUp(object sender, MouseEventArgs e)
        {
            if (isDrawing && e.Button == MouseButtons.Left)
            {
                currentPoint = e.Location;
                isDrawing = false;
                this.Invalidate();

                //calcule l'angle en degrés par rapport à la verticale (0° en haut, 180° en bas, sens horaire)
                if (startPoint.HasValue && currentPoint.HasValue)
                {
                    double deltaX = currentPoint.Value.X - startPoint.Value.X;
                    double deltaY = currentPoint.Value.Y - startPoint.Value.Y;
                    double angleInDegrees = Math.Atan2(deltaX, -deltaY) * (180.0 / Math.PI);
                    if (angleInDegrees < 0)
                        angleInDegrees += 360.0;
                    //arrondi à la dizaine la plus proche
                    int roundedAngle = (int)(Math.Round(angleInDegrees / 10.0));
                    if (roundedAngle == 0)
                        roundedAngle = 36;
                    //met à jour les TextBox
                    if (string.IsNullOrEmpty(tbPistes.Text))
                    {
                        tbPistes.Text = roundedAngle.ToString() + "-" + ((roundedAngle + 18) % 36 == 0 ? 36 : (roundedAngle + 18) % 36).ToString();
                    }
                    else
                    {
                        string pistesText = tbPistes.Text.Trim();
                        tbPistes.Text = pistesText + " " + roundedAngle.ToString() + "-" + ((roundedAngle + 18) % 36 == 0 ? 36 : (roundedAngle + 18) % 36).ToString();
                    }
                }
            }
        }

        // Dessin de la ligne
        private void panel1_Paint(object sender, PaintEventArgs e)
        {
            if (startPoint.HasValue && currentPoint.HasValue && isDrawing)
            {
                using (var pen = new Pen(Color.Red, 2))
                {
                    e.Graphics.DrawLine(pen, startPoint.Value, currentPoint.Value);
                }
            }
        }

        private void btnOK_Click(object sender, EventArgs e)
        {
            this.runways = tbPistes.Text;
            this.DialogResult = DialogResult.OK;
            this.Close();

        }

        private void btnCancel_Click(object sender, EventArgs e)
        {
            this.DialogResult = DialogResult.Cancel;
            this.Close();

        }
    }
}
