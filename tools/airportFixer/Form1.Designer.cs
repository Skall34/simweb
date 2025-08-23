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
            btnCheck = new Button();
            listView1 = new ListView();
            Message = new ColumnHeader();
            Line = new ColumnHeader();
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
            listBox1.Location = new Point(12, 353);
            listBox1.Name = "listBox1";
            listBox1.Size = new Size(776, 79);
            listBox1.TabIndex = 3;
            // 
            // btnCheck
            // 
            btnCheck.Enabled = false;
            btnCheck.Location = new Point(12, 73);
            btnCheck.Name = "btnCheck";
            btnCheck.Size = new Size(152, 23);
            btnCheck.TabIndex = 4;
            btnCheck.Text = "Check it";
            btnCheck.UseVisualStyleBackColor = true;
            btnCheck.Click += btnCheck_Click;
            // 
            // listView1
            // 
            listView1.Columns.AddRange(new ColumnHeader[] { Message, Line });
            listView1.FullRowSelect = true;
            listView1.Location = new Point(12, 132);
            listView1.Name = "listView1";
            listView1.Size = new Size(776, 215);
            listView1.TabIndex = 5;
            listView1.UseCompatibleStateImageBehavior = false;
            listView1.View = View.Details;
            listView1.MouseDoubleClick += listView1_MouseDoubleClick;
            // 
            // Message
            // 
            Message.Text = "Message";
            Message.Width = 400;
            // 
            // Line
            // 
            Line.Text = "Line";
            // 
            // Form1
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(800, 450);
            Controls.Add(listView1);
            Controls.Add(btnCheck);
            Controls.Add(listBox1);
            Controls.Add(btnFixIt);
            Controls.Add(label1);
            Controls.Add(btnOpenCSV);
            Name = "Form1";
            Text = "Form1";
            Load += Form1_Load;
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private Button btnOpenCSV;
        private Label label1;
        private Button btnFixIt;
        private ListBox listBox1;
        private Button btnCheck;
        private ListView listView1;
        private ColumnHeader Message;
        private ColumnHeader Line;
    }
}
