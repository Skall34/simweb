namespace airportFixer
{
    partial class askUpdateForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            button1 = new Button();
            button2 = new Button();
            label1 = new Label();
            label2 = new Label();
            label3 = new Label();
            tbPistes = new TextBox();
            tbLongueurs = new TextBox();
            tbSurfaces = new TextBox();
            linkLabel1 = new LinkLabel();
            tbURLUpdate = new TextBox();
            SuspendLayout();
            // 
            // button1
            // 
            button1.Anchor = AnchorStyles.Bottom | AnchorStyles.Right;
            button1.Location = new Point(347, 209);
            button1.Name = "button1";
            button1.Size = new Size(75, 23);
            button1.TabIndex = 0;
            button1.Text = "OK";
            button1.UseVisualStyleBackColor = true;
            button1.Click += button1_Click;
            // 
            // button2
            // 
            button2.Anchor = AnchorStyles.Bottom | AnchorStyles.Right;
            button2.Location = new Point(266, 209);
            button2.Name = "button2";
            button2.Size = new Size(75, 23);
            button2.TabIndex = 1;
            button2.Text = "Cancel";
            button2.UseVisualStyleBackColor = true;
            button2.Click += button2_Click;
            // 
            // label1
            // 
            label1.AutoSize = true;
            label1.Location = new Point(12, 9);
            label1.Name = "label1";
            label1.Size = new Size(37, 15);
            label1.TabIndex = 2;
            label1.Text = "Pistes";
            // 
            // label2
            // 
            label2.AutoSize = true;
            label2.Location = new Point(12, 43);
            label2.Name = "label2";
            label2.Size = new Size(63, 15);
            label2.TabIndex = 3;
            label2.Text = "Longueurs";
            // 
            // label3
            // 
            label3.AutoSize = true;
            label3.Location = new Point(12, 81);
            label3.Name = "label3";
            label3.Size = new Size(51, 15);
            label3.TabIndex = 4;
            label3.Text = "Surfaces";
            // 
            // tbPistes
            // 
            tbPistes.Location = new Point(105, 6);
            tbPistes.Name = "tbPistes";
            tbPistes.Size = new Size(289, 23);
            tbPistes.TabIndex = 5;
            // 
            // tbLongueurs
            // 
            tbLongueurs.Location = new Point(105, 40);
            tbLongueurs.Name = "tbLongueurs";
            tbLongueurs.Size = new Size(289, 23);
            tbLongueurs.TabIndex = 6;
            // 
            // tbSurfaces
            // 
            tbSurfaces.Location = new Point(105, 78);
            tbSurfaces.Name = "tbSurfaces";
            tbSurfaces.Size = new Size(289, 23);
            tbSurfaces.TabIndex = 7;
            // 
            // linkLabel1
            // 
            linkLabel1.AutoSize = true;
            linkLabel1.Location = new Point(12, 127);
            linkLabel1.Name = "linkLabel1";
            linkLabel1.Size = new Size(60, 15);
            linkLabel1.TabIndex = 8;
            linkLabel1.TabStop = true;
            linkLabel1.Text = "linkLabel1";
            linkLabel1.LinkClicked += linkLabel1_LinkClicked;
            // 
            // tbURLUpdate
            // 
            tbURLUpdate.Location = new Point(12, 157);
            tbURLUpdate.Name = "tbURLUpdate";
            tbURLUpdate.Size = new Size(382, 23);
            tbURLUpdate.TabIndex = 9;
            // 
            // askUpdateForm
            // 
            AcceptButton = button1;
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            CancelButton = button2;
            ClientSize = new Size(434, 244);
            Controls.Add(tbURLUpdate);
            Controls.Add(linkLabel1);
            Controls.Add(tbSurfaces);
            Controls.Add(tbLongueurs);
            Controls.Add(tbPistes);
            Controls.Add(label3);
            Controls.Add(label2);
            Controls.Add(label1);
            Controls.Add(button2);
            Controls.Add(button1);
            Name = "askUpdateForm";
            Text = "askUpdateForm";
            Load += askUpdateForm_Load;
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private Button button1;
        private Button button2;
        private Label label1;
        private Label label2;
        private Label label3;
        private TextBox tbPistes;
        private TextBox tbLongueurs;
        private TextBox tbSurfaces;
        private LinkLabel linkLabel1;
        private TextBox tbURLUpdate;
    }
}