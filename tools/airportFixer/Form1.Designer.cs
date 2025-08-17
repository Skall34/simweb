namespace airportFixer
{
    partial class Form1
    {
        /// <summary>
        ///  Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        ///  Clean up any resources being used.
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
        ///  Required method for Designer support - do not modify
        ///  the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            btnOpenCSV = new Button();
            label1 = new Label();
            btnFixIt = new Button();
            listBox1 = new ListBox();
            SuspendLayout();
            // 
            // btnOpenCSV
            // 
            btnOpenCSV.Location = new Point(12, 12);
            btnOpenCSV.Name = "btnOpenCSV";
            btnOpenCSV.Size = new Size(152, 26);
            btnOpenCSV.TabIndex = 0;
            btnOpenCSV.Text = "Choose airport csv file";
            btnOpenCSV.UseVisualStyleBackColor = true;
            btnOpenCSV.Click += button1_Click;
            // 
            // label1
            // 
            label1.AutoSize = true;
            label1.Location = new Point(170, 18);
            label1.Name = "label1";
            label1.Size = new Size(38, 15);
            label1.TabIndex = 1;
            label1.Text = "label1";
            // 
            // btnFixIt
            // 
            btnFixIt.Enabled = false;
            btnFixIt.Location = new Point(12, 44);
            btnFixIt.Name = "btnFixIt";
            btnFixIt.Size = new Size(152, 23);
            btnFixIt.TabIndex = 2;
            btnFixIt.Text = "Fix it";
            btnFixIt.UseVisualStyleBackColor = true;
            btnFixIt.Click += button2_Click;
            // 
            // listBox1
            // 
            listBox1.Anchor = AnchorStyles.Top | AnchorStyles.Bottom | AnchorStyles.Left | AnchorStyles.Right;
            listBox1.FormattingEnabled = true;
            listBox1.ItemHeight = 15;
            listBox1.Location = new Point(12, 248);
            listBox1.Name = "listBox1";
            listBox1.Size = new Size(776, 184);
            listBox1.TabIndex = 3;
            // 
            // Form1
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(800, 450);
            Controls.Add(listBox1);
            Controls.Add(btnFixIt);
            Controls.Add(label1);
            Controls.Add(btnOpenCSV);
            Name = "Form1";
            Text = "Form1";
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private Button btnOpenCSV;
        private Label label1;
        private Button btnFixIt;
        private ListBox listBox1;
    }
}
