namespace airportFixer
{
    partial class RunwayFinder
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
            tbPistes = new TextBox();
            btnOK = new Button();
            btnCancel = new Button();
            SuspendLayout();
            // 
            // tbPistes
            // 
            tbPistes.Anchor = AnchorStyles.Bottom | AnchorStyles.Left | AnchorStyles.Right;
            tbPistes.Location = new Point(12, 303);
            tbPistes.Name = "tbPistes";
            tbPistes.Size = new Size(223, 23);
            tbPistes.TabIndex = 0;
            // 
            // btnOK
            // 
            btnOK.Anchor = AnchorStyles.Bottom | AnchorStyles.Right;
            btnOK.Location = new Point(322, 302);
            btnOK.Name = "btnOK";
            btnOK.Size = new Size(75, 23);
            btnOK.TabIndex = 1;
            btnOK.Text = "OK";
            btnOK.UseVisualStyleBackColor = true;
            btnOK.Click += btnOK_Click;
            // 
            // btnCancel
            // 
            btnCancel.Anchor = AnchorStyles.Bottom | AnchorStyles.Right;
            btnCancel.Location = new Point(241, 302);
            btnCancel.Name = "btnCancel";
            btnCancel.Size = new Size(75, 23);
            btnCancel.TabIndex = 2;
            btnCancel.Text = "Cancel";
            btnCancel.UseVisualStyleBackColor = true;
            btnCancel.Click += btnCancel_Click;
            // 
            // RunwayFinder
            // 
            AcceptButton = btnOK;
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            CancelButton = btnCancel;
            ClientSize = new Size(409, 337);
            Controls.Add(btnCancel);
            Controls.Add(btnOK);
            Controls.Add(tbPistes);
            Name = "RunwayFinder";
            Opacity = 0.7D;
            Text = "RunwayFinder";
            Load += RunwayFinder_Load;
            Paint += panel1_Paint;
            MouseDown += panel1_MouseDown;
            MouseMove += panel1_MouseMove;
            MouseUp += panel1_MouseUp;
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private TextBox tbPistes;
        private Button btnOK;
        private Button btnCancel;
    }
}